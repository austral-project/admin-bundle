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

use Austral\AdminBundle\Exception\ModuleNotFoundException;

/**
 * Austral Module Registry.
 *
 * The definitions of the modules, built by ModuleRegistryPass at the compilation of the container. Nothing is
 * evaluated here: no service, no right.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleRegistry
{
  /**
   * @var array<string, ModuleDefinition>|null
   */
  private ?array $definitions = null;

  /**
   * @param array<string, array<string, mixed>> $rawDefinitions Normalized definitions by path, in configuration order
   */
  public function __construct(private readonly array $rawDefinitions = [])
  {
  }

  /**
   * @return array<string, ModuleDefinition> By path, in configuration order (parents before children)
   */
  public function all(): array
  {
    if (null === $this->definitions)
    {
      $this->definitions = [];
      foreach ($this->rawDefinitions as $path => $definition)
      {
        $this->definitions[(string) $path] = ModuleDefinition::fromArray($definition);
      }
    }

    return $this->definitions;
  }

  /**
   * @param string $path
   *
   * @return bool
   */
  public function has(string $path): bool
  {
    return isset($this->all()[$path]);
  }

  /**
   * @param string $path
   *
   * @return ModuleDefinition
   *
   * @throws ModuleNotFoundException
   */
  public function get(string $path): ModuleDefinition
  {
    return $this->all()[$path] ?? throw new ModuleNotFoundException($path);
  }

  /**
   * @param string $key Key of the module in the configuration
   *
   * @return ModuleDefinition|null The first module of this key
   */
  public function findByKey(string $key): ?ModuleDefinition
  {
    foreach ($this->all() as $definition)
    {
      if ($definition->getKey() === $key)
      {
        return $definition;
      }
    }

    return null;
  }

  /**
   * @return list<ModuleDefinition> Modules without parent, by navigation position
   */
  public function roots(): array
  {
    return self::sort(array_values(array_filter($this->all(), static fn (ModuleDefinition $definition): bool => null === $definition->getParentPath())));
  }

  /**
   * @param ModuleDefinition $definition
   *
   * @return list<ModuleDefinition> By navigation position
   */
  public function children(ModuleDefinition $definition): array
  {
    return self::sort(array_map($this->get(...), $definition->getChildren()));
  }

  /**
   * @param ModuleDefinition $definition
   *
   * @return ModuleDefinition|null
   */
  public function parent(ModuleDefinition $definition): ?ModuleDefinition
  {
    $parent = $definition->getParentPath();

    return null !== $parent ? $this->get($parent) : null;
  }

  /**
   * @param ModuleDefinition $definition
   *
   * @return list<ModuleDefinition> From the root to the parent
   */
  public function ancestors(ModuleDefinition $definition): array
  {
    $ancestors = [];
    while ($definition = $this->parent($definition))
    {
      array_unshift($ancestors, $definition);
    }

    return $ancestors;
  }

  /**
   * @param class-string|object $entity
   *
   * @return list<ModuleDefinition> Entity modules of this class (or of a parent class)
   */
  public function findByEntity(string|object $entity): array
  {
    $class = \is_object($entity) ? $entity::class : $entity;

    return array_values(array_filter($this->all(), static fn (ModuleDefinition $definition): bool => null !== ($entityClass = $definition->getEntityClass()) && is_a($class, $entityClass, true)));
  }

  /**
   * Stable sort by navigation position.
   *
   * @param list<ModuleDefinition> $definitions
   *
   * @return list<ModuleDefinition>
   */
  private static function sort(array $definitions): array
  {
    $order = array_flip(array_map(static fn (ModuleDefinition $definition): string => $definition->getPath(), $definitions));
    usort($definitions, static fn (ModuleDefinition $a, ModuleDefinition $b): int => [$a->getNavigationPosition(), $order[$a->getPath()]] <=> [$b->getNavigationPosition(), $order[$b->getPath()]]);

    return $definitions;
  }
}
