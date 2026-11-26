<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\DependencyInjection\Compiler;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Attribute\AsAdminModule;
use Austral\AdminBundle\DependencyInjection\AustralAdminExtension;
use Austral\AdminBundle\DependencyInjection\Configuration;
use Austral\AdminBundle\Export\Exporter;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Module\ModuleFactory;
use Austral\AdminBundle\Module\ModuleRegistry;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Variant\ModuleVariantProviderInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Austral Module Registry Pass.
 *
 * Reads "austral_admin.modules" and the attributes AsAdminModule once, at the compilation of the container: the definitions are checked (Admin
 * services, entity managers, actions, formats), their variant providers chosen, and the registry and the service
 * locators of the module factory are built. A mistake in the configuration fails here, not on a request.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleRegistryPass implements CompilerPassInterface
{
  use PriorityTaggedServiceTrait;

  public const string PARAMETER_ROUTES_HASH = 'austral_admin.routes_hash';

  private const string SEGMENT = '/^[A-Za-z0-9][A-Za-z0-9_-]*$/';

  /**
   * @param ContainerBuilder $container
   *
   * @return void
   */
  public function process(ContainerBuilder $container): void
  {
    if (!$container->hasDefinition(ModuleRegistry::class))
    {
      return;
    }
    $configs = $container->hasParameter(AustralAdminExtension::PARAMETER_MODULES) ? $container->getParameter(AustralAdminExtension::PARAMETER_MODULES) : [];
    $container->getParameterBag()->remove(AustralAdminExtension::PARAMETER_MODULES);
    $modules = $this->modules($container, \is_array($configs) ? array_values(array_filter($configs, 'is_array')) : []);

    $definitions = [];
    foreach ($modules as $key => $module)
    {
      if (\is_array($module))
      {
        $this->collect($container, (string) $key, $module, null, $definitions);
      }
    }

    $providers = array_map('strval', $this->findAndSortTaggedServices(AustralAdminExtension::TAG_VARIANT_PROVIDER, $container));
    $admins = [];
    $entityManagers = [];
    $variantProviders = [];
    foreach ($definitions as $path => $definition)
    {
      if (null !== $definition['admin'])
      {
        $admins[$definition['admin']] = new Reference($definition['admin']);
      }
      if (null !== $definition['entity_manager'])
      {
        $entityManagers[$definition['entity_manager']] = new Reference($definition['entity_manager']);
        $value = ModuleDefinition::fromArray($definition);
        foreach ($providers as $provider)
        {
          /** @var class-string<ModuleVariantProviderInterface> $class */
          $class = $this->serviceClass($container, $provider);
          if ($class::supports($value))
          {
            $definitions[$path]['variant_providers'][] = $provider;
            $variantProviders[$provider] = new Reference($provider);
          }
        }
      }
    }

    $container->getDefinition(ModuleRegistry::class)->setArgument('$rawDefinitions', $definitions);
    // The routes are built from the modules: their cache is refreshed when the modules change.
    $container->setParameter(self::PARAMETER_ROUTES_HASH, hash('xxh128', serialize([
      $definitions,
      $container->hasParameter('austral_admin.path') ? $container->getParameter('austral_admin.path') : null,
      $container->hasParameter('austral_admin.languages.content') ? $container->getParameter('austral_admin.languages.content') : null,
    ])));
    $container->getDefinition(ModuleFactory::class)
      ->setArgument('$admins', ServiceLocatorTagPass::register($container, $admins))
      ->setArgument('$entityManagers', ServiceLocatorTagPass::register($container, $entityManagers))
      ->setArgument('$variantProviders', ServiceLocatorTagPass::register($container, $variantProviders));
  }

  /**
   * The modules of the attributes AsAdminModule first, then the configurations (bundles, application): the YAML
   * wins.
   *
   * @param ContainerBuilder              $container
   * @param list<array<array-key, mixed>> $configs   The "modules" of each configuration of austral_admin
   *
   * @return array<array-key, mixed>
   */
  private function modules(ContainerBuilder $container, array $configs): array
  {
    $declared = [];
    $collectPaths = static function (array $modules, string $prefix) use (&$collectPaths, &$declared): void {
      foreach ($modules as $key => $module)
      {
        $declared[$prefix.$key] = true;
        if (\is_array($module) && \is_array($module['children'] ?? null))
        {
          $collectPaths($module['children'], $prefix.$key.'/');
        }
      }
    };
    foreach ($configs as $config)
    {
      $collectPaths($config, '');
    }

    $attributeModules = [];
    $parents = [];
    foreach (array_keys($container->findTaggedServiceIds(AustralAdminExtension::TAG_ADMIN)) as $id)
    {
      $class = $this->serviceClass($container, (string) $id);
      $reflection = $container->getReflectionClass($class);
      foreach ($reflection?->getAttributes(AsAdminModule::class) ?? [] as $attribute)
      {
        $module = $attribute->newInstance();
        $keys = null !== $module->parent ? [...explode('/', trim($module->parent, '/')), $module->getKey($class)] : [$module->getKey($class)];
        $keyPath = implode('/', $keys);
        if (isset($parents[$keyPath]))
        {
          throw new InvalidArgumentException(\sprintf('Two attributes AsAdminModule declare the module "%s" (%s and %s).', $keyPath, $parents[$keyPath]['class'], $class));
        }
        $parents[$keyPath] = ['class' => $class, 'parent' => $module->parent];
        $declared[$keyPath] = true;

        $node = &$attributeModules;
        foreach (\array_slice($keys, 0, -1) as $key)
        {
          $node[$key] ??= [];
          $node[$key]['children'] ??= [];
          $node = &$node[$key]['children'];
        }
        $node[end($keys)] = $module->toConfig((string) $id);
        unset($node);
      }
    }
    foreach ($parents as $keyPath => ['class' => $class, 'parent' => $parent])
    {
      if (null !== $parent && !isset($declared[trim($parent, '/')]))
      {
        throw new InvalidArgumentException(\sprintf('The parent "%s" of the module "%s" (attribute AsAdminModule of %s) is not a module: give the keys of a module defined in YAML or by another attribute.', $parent, $keyPath, $class));
      }
    }

    $configuration = new Processor()->processConfiguration(new Configuration(), array_map(static fn (array $modules): array => ['modules' => $modules], [$attributeModules, ...$configs]));

    return $configuration['modules'];
  }

  /**
   * @param ContainerBuilder                    $container
   * @param string                              $key
   * @param array<string, mixed>                $module
   * @param string|null                         $parent
   * @param array<string, array<string, mixed>> $definitions
   *
   * @return void
   */
  private function collect(ContainerBuilder $container, string $key, array $module, ?string $parent, array &$definitions): void
  {
    if (false === ($module['enabled'] ?? true))
    {
      return;
    }
    $route = $module['route'] ?? $key;
    if (!\is_string($route) || !preg_match(self::SEGMENT, $route))
    {
      throw new InvalidArgumentException(\sprintf('The route "%s" of the admin module "%s" must contain only letters, digits, "_" and "-".', \is_string($route) ? $route : get_debug_type($route), $key));
    }
    $path = null !== $parent ? "{$parent}/{$route}" : $route;
    if (isset($definitions[$path]))
    {
      throw new InvalidArgumentException(\sprintf('Two admin modules have the path "%s" (key "%s").', $path, $key));
    }

    $admin = self::string($module['class'] ?? null);
    if (null !== $admin)
    {
      $this->registerAdmin($container, $admin, $path);
    }
    $entityManager = self::string($module['entity_manager'] ?? null);
    if (null !== $entityManager && !$container->has($entityManager))
    {
      throw new InvalidArgumentException(\sprintf('The entity manager "%s" of the admin module "%s" is not a service.', $entityManager, $path));
    }
    if (null !== $entityManager && null === $admin)
    {
      throw new InvalidArgumentException(\sprintf('The admin module "%s" has an entity manager and no "class": an entity module needs an Admin class.', $path));
    }

    $sortable = $module['sortable'] ?? false;
    $sortable = match (true)
    {
      true === $sortable, '1' === $sortable, 'true' === $sortable => 'position',
      \is_string($sortable) && '' !== $sortable && 'false' !== $sortable && '0' !== $sortable => $sortable,
      default => null,
    };

    $definition = [
      'key' => $key,
      'path' => $path,
      'route' => $route,
      'parent' => $parent,
      'name' => self::string($module['name'] ?? null) ?? $key,
      'picto' => self::string($module['picto'] ?? null),
      'picto_tile' => self::string($module['pictoTile'] ?? null),
      'admin' => $admin,
      'entity_manager' => $entityManager,
      'entity' => self::string($module['entity'] ?? null) ?? (null !== $entityManager ? $this->entityClass($container, $entityManager) : null),
      'navigation' => [
        'enabled' => (bool) ($module['navigation']['enabled'] ?? true),
        'position' => (int) ($module['navigation']['position'] ?? 0),
      ],
      'translate' => self::string($module['translate'] ?? null),
      'sortable' => null !== $entityManager ? $sortable : null,
      'truncate' => null !== $entityManager && (bool) ($module['truncate'] ?? false),
      'actions' => null === $entityManager && null !== $admin ? self::actions($module['actions'] ?? [], $path, true) : [],
      'extend_actions' => null !== $entityManager ? self::actions($module['extendActions'] ?? [], $path, false) : [],
      'disabled_actions' => self::disabledActions($module['disabledActions'] ?? [], $path),
      'download_formats' => null !== $entityManager ? self::formats($module['downloadFormats'] ?? [], $path) : [],
      'security_key' => self::string($module['security_key'] ?? null) ?? $path,
      'children' => [],
      'variant_providers' => [],
    ];
    if (null !== $definition['admin'] && null === $entityManager && !isset($definition['actions'][ModuleDefinition::PAGE_INDEX]))
    {
      throw new InvalidArgumentException(\sprintf('The page module "%s" needs an action "index" (method of its Admin).', $path));
    }
    $definitions[$path] = $definition;
    if (null !== $parent)
    {
      $definitions[$parent]['children'][] = $path;
    }

    foreach (\is_array($module['children'] ?? null) ? $module['children'] : [] as $childKey => $child)
    {
      if (\is_array($child))
      {
        $this->collect($container, (string) $childKey, $child, $path, $definitions);
      }
    }
  }

  /**
   * The Admin is a service tagged "austral.admin"; a class that is not a service is registered (without
   * arguments).
   *
   * @param ContainerBuilder $container
   * @param string           $id
   * @param string           $path
   *
   * @return void
   */
  private function registerAdmin(ContainerBuilder $container, string $id, string $path): void
  {
    if (!$container->has($id))
    {
      if (!class_exists($id))
      {
        throw new InvalidArgumentException(\sprintf('The Admin "%s" of the admin module "%s" is neither a service nor a class.', $id, $path));
      }
      $container->setDefinition($id, new Definition($id))->addTag(AustralAdminExtension::TAG_ADMIN);
    }
    $class = $this->serviceClass($container, $id);
    if (!is_a($class, Admin::class, true))
    {
      throw new InvalidArgumentException(\sprintf('The Admin "%s" of the admin module "%s" must extend %s.', $id, $path, Admin::class));
    }
  }

  /**
   * @param ContainerBuilder $container
   * @param string           $id
   *
   * @return class-string
   */
  private function serviceClass(ContainerBuilder $container, string $id): string
  {
    $definition = $container->findDefinition($id);
    $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
    if (!\is_string($class) || !class_exists($class))
    {
      throw new InvalidArgumentException(\sprintf('The class of the service "%s" cannot be found.', $id));
    }

    return $class;
  }

  /**
   * The entity class given to the entity manager ("$class" argument), null when unknown.
   *
   * @param ContainerBuilder $container
   * @param string           $id
   *
   * @return string|null
   */
  private function entityClass(ContainerBuilder $container, string $id): ?string
  {
    $arguments = $container->findDefinition($id)->getArguments();
    $class = $arguments['$class'] ?? $arguments[2] ?? null;
    $class = \is_string($class) ? $container->getParameterBag()->resolveValue($class) : null;

    return \is_string($class) && '' !== $class ? $class : null;
  }

  /**
   * @param mixed  $actions
   * @param string $path
   * @param bool   $page
   *
   * @return array<string, string>
   */
  private static function actions(mixed $actions, string $path, bool $page): array
  {
    $normalized = [];
    foreach (\is_array($actions) ? $actions : [] as $name => $method)
    {
      $name = strtolower((string) $name);
      if (!preg_match('/^[a-z][a-z0-9_-]*$/', $name) || \in_array($name, ModuleAction::ENTITY, true) || str_starts_with($name, AdminUrlGenerator::VARIANTS_ROUTE) || (!$page && ModuleDefinition::PAGE_INDEX === $name))
      {
        throw new InvalidArgumentException(\sprintf('The action "%s" of the admin module "%s" is not a valid name (lowercase letters, digits, "_", "-"; not a name of the admin: %s).', $name, $path, implode(', ', ModuleAction::ENTITY)));
      }
      if (!\is_string($method) || '' === $method)
      {
        throw new InvalidArgumentException(\sprintf('The action "%s" of the admin module "%s" needs a method name.', $name, $path));
      }
      $normalized[$name] = $method;
    }

    return $normalized;
  }

  /**
   * @param mixed  $actions
   * @param string $path
   *
   * @return list<string>
   */
  private static function disabledActions(mixed $actions, string $path): array
  {
    $normalized = [];
    foreach (\is_array($actions) ? $actions : [] as $action)
    {
      $action = ModuleAction::normalize((string) $action);
      if (ModuleAction::LIST === $action)
      {
        throw new InvalidArgumentException(\sprintf('The action "list" of the admin module "%s" cannot be disabled: disable the module ("enabled: false").', $path));
      }
      $normalized[] = $action;
    }

    return array_values(array_unique($normalized));
  }

  /**
   * @param mixed  $formats
   * @param string $path
   *
   * @return list<string>
   */
  private static function formats(mixed $formats, string $path): array
  {
    $normalized = [];
    foreach (\is_array($formats) ? $formats : [] as $format)
    {
      $format = strtolower((string) $format);
      if (!\in_array($format, Exporter::FORMATS, true))
      {
        throw new InvalidArgumentException(\sprintf('The export format "%s" of the admin module "%s" is unknown: %s.', $format, $path, implode(', ', Exporter::FORMATS)));
      }
      if ('xlsx' === $format && !class_exists(Spreadsheet::class))
      {
        throw new InvalidArgumentException(\sprintf('The export format "xlsx" of the admin module "%s" needs phpoffice/phpspreadsheet: composer require phpoffice/phpspreadsheet.', $path));
      }
      $normalized[] = $format;
    }

    return array_values(array_unique($normalized));
  }

  /**
   * @param mixed $value
   *
   * @return string|null
   */
  private static function string(mixed $value): ?string
  {
    return \is_string($value) && '' !== $value ? $value : null;
  }
}
