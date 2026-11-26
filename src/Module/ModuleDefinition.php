<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Module;

use Symfony\Component\String\UnicodeString;

/**
 * Austral Module Definition.
 *
 * Configuration of a module, read from "austral_admin.modules" and checked at the compilation of the container.
 * Immutable: the same definition is used by every request.
 *
 * Three kinds of modules:
 * - entity module: an entity manager, an Admin class (list, form, actions);
 * - page module: an Admin class without entity manager, its "actions" are methods of the Admin;
 * - container module: neither, it shows the tiles of its children.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @phpstan-type DefinitionArray array{
 *   key: string,
 *   path: string,
 *   route: string,
 *   parent: ?string,
 *   name: string,
 *   picto: ?string,
 *   picto_tile: ?string,
 *   admin: ?string,
 *   entity_manager: ?string,
 *   entity: ?string,
 *   navigation: array{enabled: bool, position: int},
 *   translate: ?string,
 *   sortable: ?string,
 *   truncate: bool,
 *   actions: array<string, string>,
 *   extend_actions: array<string, string>,
 *   disabled_actions: list<string>,
 *   download_formats: list<string>,
 *   security_key: string,
 *   children: list<string>,
 *   variant_providers: list<string>
 * }
 */
final class ModuleDefinition
{
  public const string TYPE_ENTITY = 'entity';
  public const string TYPE_PAGE = 'page';
  public const string TYPE_CONTAINER = 'container';

  /**
   * Method of a page module called for its index.
   */
  public const string PAGE_INDEX = 'index';

  /**
   * @param array<string, mixed> $definition
   *
   * @phpstan-param DefinitionArray $definition
   */
  private function __construct(private readonly array $definition)
  {
  }

  /**
   * @param array<string, mixed> $definition Normalized by the compiler pass
   *
   * @return self
   */
  public static function fromArray(array $definition): self
  {
    $defaults = [
      'key' => null,
      'path' => null,
      'route' => null,
      'parent' => null,
      'name' => null,
      'picto' => null,
      'picto_tile' => null,
      'admin' => null,
      'entity_manager' => null,
      'entity' => null,
      'navigation' => ['enabled' => true, 'position' => 0],
      'translate' => null,
      'sortable' => null,
      'truncate' => false,
      'actions' => [],
      'extend_actions' => [],
      'disabled_actions' => [],
      'download_formats' => [],
      'security_key' => null,
      'children' => [],
      'variant_providers' => [],
    ];
    $unknown = array_diff_key($definition, $defaults);
    if ($unknown)
    {
      throw new \InvalidArgumentException(\sprintf('Unknown keys "%s" in the definition of a module.', implode('", "', array_keys($unknown))));
    }
    $definition += $defaults;
    foreach (['key', 'path'] as $key)
    {
      if (!\is_string($definition[$key]) || '' === $definition[$key])
      {
        throw new \InvalidArgumentException(\sprintf('The definition of a module needs a "%s".', $key));
      }
    }
    $definition['route'] ??= basename($definition['path']);
    $definition['name'] ??= $definition['key'];
    $definition['security_key'] ??= $definition['path'];
    $definition['navigation'] += ['enabled' => true, 'position' => 0];

    /* @phpstan-var DefinitionArray $definition */
    return new self($definition);
  }

  /**
   * @return array<string, mixed>
   *
   * @phpstan-return DefinitionArray
   */
  public function toArray(): array
  {
    return $this->definition;
  }

  /**
   * @return string Key of the module in the configuration
   */
  public function getKey(): string
  {
    return $this->definition['key'];
  }

  /**
   * @return string Path of the module: the routes of its parents and its route, joined by "/" (security/users)
   */
  public function getPath(): string
  {
    return $this->definition['path'];
  }

  /**
   * @return string Segment of the URL of the module
   */
  public function getRoute(): string
  {
    return $this->definition['route'];
  }

  /**
   * @return string|null Path of the parent module
   */
  public function getParentPath(): ?string
  {
    return $this->definition['parent'];
  }

  /**
   * @return list<string> Paths of the children, in configuration order
   */
  public function getChildren(): array
  {
    return $this->definition['children'];
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return match (true)
    {
      null !== $this->definition['entity_manager'] => self::TYPE_ENTITY,
      null !== $this->definition['admin'] => self::TYPE_PAGE,
      default => self::TYPE_CONTAINER,
    };
  }

  /**
   * @return bool
   */
  public function isEntity(): bool
  {
    return self::TYPE_ENTITY === $this->getType();
  }

  /**
   * @return bool
   */
  public function isPage(): bool
  {
    return self::TYPE_PAGE === $this->getType();
  }

  /**
   * @return bool
   */
  public function isContainer(): bool
  {
    return self::TYPE_CONTAINER === $this->getType();
  }

  /**
   * @return string Name of the module (configuration, not translated)
   */
  public function getName(): string
  {
    return $this->definition['name'];
  }

  /**
   * @return string Key of the translations of the module: pages.names.<key>.singular / .plural (domain austral)
   */
  public function getTranslationKey(): string
  {
    return new UnicodeString($this->definition['path'])->camel()->toString();
  }

  /**
   * @return string|null Gender of the translations (translate key of the 3.1: male_v, female_c...)
   */
  public function getTranslate(): ?string
  {
    return $this->definition['translate'];
  }

  /**
   * @return string|null
   */
  public function getPicto(): ?string
  {
    return $this->definition['picto'];
  }

  /**
   * @return string|null
   */
  public function getPictoTile(): ?string
  {
    return $this->definition['picto_tile'];
  }

  /**
   * @return string|null Service id of the Admin class
   */
  public function getAdminId(): ?string
  {
    return $this->definition['admin'];
  }

  /**
   * @return string|null Service id of the entity manager
   */
  public function getEntityManagerId(): ?string
  {
    return $this->definition['entity_manager'];
  }

  /**
   * @return class-string|null Class of the entities, when known
   */
  public function getEntityClass(): ?string
  {
    /* @var class-string|null */
    return $this->definition['entity'];
  }

  /**
   * @return bool
   */
  public function isNavigationEnabled(): bool
  {
    return $this->definition['navigation']['enabled'];
  }

  /**
   * @return int
   */
  public function getNavigationPosition(): int
  {
    return $this->definition['navigation']['position'];
  }

  /**
   * @return string|null Field holding the position of the entities, null when the module is not sortable
   */
  public function getSortableField(): ?string
  {
    return $this->definition['sortable'];
  }

  /**
   * @return bool
   */
  public function isTruncateEnabled(): bool
  {
    return $this->definition['truncate'];
  }

  /**
   * @return array<string, string> Actions of a page module: name => method of the Admin ("index" for the module)
   */
  public function getActions(): array
  {
    return $this->definition['actions'];
  }

  /**
   * @return array<string, string> Actions on an entity: name => method of the Admin
   */
  public function getExtendActions(): array
  {
    return $this->definition['extend_actions'];
  }

  /**
   * @return list<string>
   */
  public function getDisabledActions(): array
  {
    return $this->definition['disabled_actions'];
  }

  /**
   * @param string $action
   *
   * @return bool
   */
  public function isDisabled(string $action): bool
  {
    return \in_array($action, $this->definition['disabled_actions'], true);
  }

  /**
   * @return list<string> Export formats (csv, json, xlsx), none when the module cannot be exported
   */
  public function getDownloadFormats(): array
  {
    return $this->definition['download_formats'];
  }

  /**
   * @return string Base of the roles of the module (security_key, the path by default)
   */
  public function getSecurityKey(): string
  {
    return $this->definition['security_key'];
  }

  /**
   * @return list<string> Service ids of the variant providers, one by level
   */
  public function getVariantProviders(): array
  {
    return $this->definition['variant_providers'];
  }

  /**
   * @return bool
   */
  public function hasVariants(): bool
  {
    return [] !== $this->definition['variant_providers'];
  }

  /**
   * @return int Number of variant levels
   */
  public function getVariantLevels(): int
  {
    return \count($this->definition['variant_providers']);
  }
}
