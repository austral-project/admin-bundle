<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Routing;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\ListBundle\Column\ChoicesColumn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Austral Admin Url Generator.
 *
 * URLs of the modules. The variants of the module and its language are added: a link of a page stays in the
 * same category and the same language.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminUrlGenerator
{
  public const string ROUTE_PREFIX = 'austral_admin.module.';
  public const string VARIANT_PARAMETER = 'variant';
  public const string VARIANTS_ROUTE = 'variants';

  /**
   * @param UrlGeneratorInterface $router
   */
  public function __construct(private readonly UrlGeneratorInterface $router)
  {
  }

  /**
   * @param string $path   Path of the module
   * @param string $action Action, or "variants<level>" for the tiles of a level of variants
   *
   * @return string
   */
  public static function routeName(string $path, string $action): string
  {
    return self::ROUTE_PREFIX.str_replace('/', '.', $path).'.'.$action;
  }

  /**
   * @param int $level
   *
   * @return string Name of the route parameter of a level of variant
   */
  public static function variantParameter(int $level): string
  {
    return self::VARIANT_PARAMETER.$level;
  }

  /**
   * @param Module               $module
   * @param string               $action
   * @param array<string, mixed> $parameters
   * @param int                  $referenceType
   *
   * @return string
   */
  public function module(Module $module, string $action = ModuleAction::LIST, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
  {
    $definition = $module->getDefinition();
    $action = ModuleAction::normalize($action);
    foreach ($module->getVariantKeys() as $level => $key)
    {
      $parameters += [self::variantParameter($level) => $key];
    }
    if (ModuleAction::LIST === $action && $definition->isEntity() && !$module->hasAllVariants())
    {
      return $this->router->generate(self::routeName($definition->getPath(), self::VARIANTS_ROUTE.\count($module->getVariants())), $parameters, $referenceType);
    }
    if (null !== $module->getLanguage() && \in_array($action, ModuleAction::WITH_LANGUAGE, true))
    {
      $parameters += ['language' => $module->getLanguage()];
    }

    return $this->router->generate(self::routeName($definition->getPath(), $action), $parameters, $referenceType);
  }

  /**
   * URL of a module without variant chosen (navigation, tiles).
   *
   * @param ModuleDefinition     $definition
   * @param string               $action
   * @param array<string, mixed> $parameters
   * @param int                  $referenceType
   *
   * @return string
   */
  public function definition(ModuleDefinition $definition, string $action = ModuleAction::LIST, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
  {
    $action = ModuleAction::normalize($action);
    if (ModuleAction::LIST === $action && $definition->hasVariants())
    {
      $action = self::VARIANTS_ROUTE.'0';
    }

    return $this->router->generate(self::routeName($definition->getPath(), $action), $parameters, $referenceType);
  }

  /**
   * @param Module               $module
   * @param string               $action
   * @param object               $object
   * @param array<string, mixed> $parameters
   * @param int                  $referenceType
   *
   * @return string
   */
  public function object(Module $module, string $action, object $object, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
  {
    return $this->module($module, $action, ['id' => self::id($object)] + $parameters, $referenceType);
  }

  /**
   * URL of the toggle action (POST) of a column, the value in the query string.
   *
   * @param Module $module
   * @param object $object
   * @param string $field
   * @param mixed  $value
   *
   * @return string
   */
  public function toggle(Module $module, object $object, string $field, mixed $value): string
  {
    return $this->object($module, ModuleAction::TOGGLE, $object, ['field' => $field, 'value' => ChoicesColumn::choiceKey($value) ?? '']);
  }

  /**
   * URL of a page of the admin that is not a module (index, login...).
   *
   * @param string               $route
   * @param array<string, mixed> $parameters
   * @param int                  $referenceType
   *
   * @return string
   */
  public function route(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
  {
    return $this->router->generate($route, $parameters, $referenceType);
  }

  /**
   * @param object $object
   *
   * @return string
   */
  public static function id(object $object): string
  {
    if (!method_exists($object, 'getId'))
    {
      throw new \InvalidArgumentException(\sprintf('The entity "%s" has no getId() method.', $object::class));
    }
    $id = $object->getId();
    if (null === $id || '' === (string) $id)
    {
      throw new \InvalidArgumentException(\sprintf('The entity "%s" has no id yet.', $object::class));
    }

    return (string) $id;
  }
}
