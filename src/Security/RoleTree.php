<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Security;

use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Module\ModuleRegistry;

/**
 * Austral Role Tree.
 *
 * The roles of the modules, in the order of the navigation, for the form of the groups and the command
 * austral:admin:roles.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class RoleTree
{
  /**
   * @param ModuleRegistry $registry
   */
  public function __construct(private readonly ModuleRegistry $registry)
  {
  }

  /**
   * @return list<array{definition: ModuleDefinition, depth: int, roles: array<string, string>}> Depth first
   */
  public function modules(): array
  {
    $modules = [];
    $walk = function (array $definitions, int $depth) use (&$walk, &$modules): void {
      /** @var ModuleDefinition $definition */
      foreach ($definitions as $definition)
      {
        $modules[] = ['definition' => $definition, 'depth' => $depth, 'roles' => ModuleRoles::roles($definition)];
        $walk($this->registry->children($definition), $depth + 1);
      }
    };
    $walk($this->registry->roots(), 0);

    return $modules;
  }

  /**
   * @return array<string, string> Role => name ("<module name> - <action>")
   */
  public function roles(): array
  {
    $roles = [];
    foreach ($this->modules() as $module)
    {
      foreach ($module['roles'] as $role => $action)
      {
        $roles[$role] ??= self::name($module['definition'], $action);
      }
    }

    return $roles;
  }

  /**
   * @param ModuleDefinition $definition
   * @param string           $action
   *
   * @return string Name of the role, saved in the database
   */
  public static function name(ModuleDefinition $definition, string $action): string
  {
    return "{$definition->getName()} - {$action}";
  }
}
