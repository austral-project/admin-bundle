<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Attribute;

/**
 * Austral As Admin Module.
 *
 * Declares a module on its Admin class, instead of an entry of "austral_admin.modules":
 *
 *   #[AsAdminModule(key: 'categories', entityManager: 'app.entity_manager.category', parent: 'catalog')]
 *   final class CategoryAdmin extends Admin
 *
 * The values are the keys of the YAML configuration and go through the same checks. The YAML wins: the modules
 * of the bundles and of config/packages/austral_admin.yaml change or disable a module of an attribute
 * (catalog: { children: { categories: { enabled: false } } }). The class is a service tagged "austral.admin"
 * (automatic for the Admin classes of the application); the attribute can be repeated (one class, several modules).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class AsAdminModule
{
  /**
   * @param string|null                           $key             Key of the module, from the class name by default (CategoryAdmin: category)
   * @param string|null                           $parent          Keys of the parent module joined by "/" (security, catalog/products), it must be defined
   * @param string|null                           $entityManager   Service id of the entity manager (entity module)
   * @param array<string, string>                 $actions         Page module: action => method ("index" required)
   * @param array<string, string>                 $extendActions   Entity module: action on an entity => method
   * @param list<string>                          $disabledActions
   * @param list<string>                          $downloadFormats
   * @param array{enabled?: bool, position?: int} $navigation
   * @param bool|string                           $sortable        true (field "position") or the position field
   */
  public function __construct(
    public readonly ?string $key = null,
    public readonly ?string $parent = null,
    public readonly ?string $name = null,
    public readonly ?string $entityManager = null,
    public readonly ?string $entity = null,
    public readonly ?string $route = null,
    public readonly ?string $picto = null,
    public readonly ?string $pictoTile = null,
    public readonly ?string $translate = null,
    public readonly bool|string $sortable = false,
    public readonly bool $truncate = false,
    public readonly array $actions = [],
    public readonly array $extendActions = [],
    public readonly array $disabledActions = [],
    public readonly array $downloadFormats = [],
    public readonly array $navigation = [],
    public readonly ?string $securityKey = null,
    public readonly bool $enabled = true,
  ) {
  }

  /**
   * @param string $class Class of the Admin
   *
   * @return string The key of the module
   */
  public function getKey(string $class): string
  {
    if (null !== $this->key)
    {
      return $this->key;
    }
    $name = (string) preg_replace('/Admin$/', '', substr(strrchr('\\'.$class, '\\') ?: $class, 1));

    return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', '' !== $name ? $name : 'admin'));
  }

  /**
   * @param string $serviceId Service id of the Admin, its "class" in the configuration
   *
   * @return array<string, mixed> The module as an entry of "austral_admin.modules" (only the values set)
   */
  public function toConfig(string $serviceId): array
  {
    $config = [
      'enabled' => $this->enabled,
      'class' => $serviceId,
      'name' => $this->name,
      'entity_manager' => $this->entityManager,
      'entity' => $this->entity,
      'route' => $this->route,
      'picto' => $this->picto,
      'pictoTile' => $this->pictoTile,
      'translate' => $this->translate,
      'security_key' => $this->securityKey,
    ];
    $config = array_filter($config, static fn (mixed $value): bool => null !== $value);
    if (false !== $this->sortable)
    {
      $config['sortable'] = $this->sortable;
    }
    if ($this->truncate)
    {
      $config['truncate'] = true;
    }
    foreach (['actions', 'extendActions', 'disabledActions', 'downloadFormats', 'navigation'] as $key)
    {
      if ([] !== $this->{$key})
      {
        $config[$key] = $this->{$key};
      }
    }

    return $config;
  }
}
