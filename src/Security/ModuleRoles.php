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

use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Module\ModuleDefinition;
use Symfony\Component\String\UnicodeString;

/**
 * Austral Module Roles.
 *
 * Names of the roles of the modules, the same as in 3.1: ROLE_ followed by the security key (the path of the
 * module by default) in upper snake case, then the suffix of the action (ROLE_SECURITY_USERS_EDIT).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleRoles
{
  /**
   * @param string $securityKey
   *
   * @return string Role of the module
   */
  public static function base(string $securityKey): string
  {
    return 'ROLE_'.strtoupper(new UnicodeString($securityKey)->replace('-', '_')->snake()->toString());
  }

  /**
   * @param ModuleDefinition $definition
   * @param string           $action     An action of ModuleAction, an action of a page module or an extra action
   *
   * @return string
   */
  public static function role(ModuleDefinition $definition, string $action): string
  {
    $base = self::base($definition->getSecurityKey());
    $action = ModuleAction::normalize($action);
    if (ModuleAction::LIST === $action || ($definition->isPage() && ModuleDefinition::PAGE_INDEX === $action))
    {
      return $base;
    }
    $suffix = \array_key_exists($action, ModuleAction::ROLE_SUFFIXES) ? ModuleAction::ROLE_SUFFIXES[$action] : strtoupper(str_replace('-', '_', $action));

    return "{$base}_{$suffix}";
  }

  /**
   * Roles of a module, as they can be given to a group.
   *
   * @param ModuleDefinition $definition
   *
   * @return array<string, string> Role => action
   */
  public static function roles(ModuleDefinition $definition): array
  {
    $roles = [self::role($definition, ModuleAction::LIST) => ModuleAction::LIST];
    if ($definition->isEntity())
    {
      foreach (ModuleAction::ENTITY as $action)
      {
        if (ModuleAccess::isAvailable($definition, $action))
        {
          $roles[self::role($definition, $action)] ??= $action;
        }
      }
      foreach (array_keys($definition->getExtendActions()) as $action)
      {
        if (ModuleAccess::isAvailable($definition, $action))
        {
          $roles[self::role($definition, $action)] ??= $action;
        }
      }
    }
    elseif ($definition->isPage())
    {
      foreach (array_keys($definition->getActions()) as $action)
      {
        if (ModuleDefinition::PAGE_INDEX !== $action && ModuleAccess::isAvailable($definition, $action))
        {
          $roles[self::role($definition, $action)] ??= $action;
        }
      }
    }

    return $roles;
  }
}
