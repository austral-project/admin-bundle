<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\AdminBundle\Tests\Unit;

use Austral\AdminBundle\Module\ModuleDefinition;

/**
 * Definitions of modules for the unit tests, as ModuleRegistryPass builds them.
 */
final class ModuleDefinitions
{
  /**
   * @param array<string, mixed> $definition
   */
  public static function entity(string $path = 'pages', array $definition = []): ModuleDefinition
  {
    return ModuleDefinition::fromArray(self::raw($path, $definition + ['admin' => 'App\\Admin\\PageAdmin', 'entity_manager' => 'app.entity_manager.page']));
  }

  /**
   * @param array<string, mixed> $definition
   */
  public static function page(string $path = 'tools', array $definition = []): ModuleDefinition
  {
    return ModuleDefinition::fromArray(self::raw($path, $definition + ['admin' => 'App\\Admin\\ToolAdmin', 'actions' => ['index' => 'index']]));
  }

  /**
   * @param array<string, mixed> $definition
   */
  public static function container(string $path = 'security', array $definition = []): ModuleDefinition
  {
    return ModuleDefinition::fromArray(self::raw($path, $definition));
  }

  /**
   * @param array<string, mixed> $definition
   *
   * @return array<string, mixed>
   */
  public static function raw(string $path, array $definition = []): array
  {
    return $definition + ['key' => basename($path), 'path' => $path, 'parent' => str_contains($path, '/') ? \dirname($path) : null];
  }
}
