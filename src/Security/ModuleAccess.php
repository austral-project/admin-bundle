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
use Austral\AdminBundle\Module\ModuleRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Austral Module Access.
 *
 * Rights on the modules, evaluated when they are asked (not for every module on every request). An action is
 * granted when it is available on the module (enabled by its configuration) and the user has its role. A
 * container module (no Admin, only children) is also open to a user who opens one of its children.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleAccess
{
  /**
   * @param AuthorizationCheckerInterface $authorizationChecker
   * @param ModuleRegistry|null           $registry             For the children of the container modules
   */
  public function __construct(
    private readonly AuthorizationCheckerInterface $authorizationChecker,
    private readonly ?ModuleRegistry $registry = null,
  ) {
  }

  /**
   * @param ModuleDefinition $definition
   * @param string           $action
   *
   * @return bool
   */
  public function isGranted(ModuleDefinition $definition, string $action): bool
  {
    $action = ModuleAction::normalize($action);
    if (!self::isAvailable($definition, $action))
    {
      return false;
    }
    if (ModuleAction::LIST !== $action && !$this->authorizationChecker->isGranted(ModuleRoles::role($definition, ModuleAction::LIST)))
    {
      return false;
    }
    if ($this->authorizationChecker->isGranted(ModuleRoles::role($definition, $action)))
    {
      return true;
    }
    if ($definition->isContainer() && null !== $this->registry)
    {
      foreach ($this->registry->children($definition) as $child)
      {
        if ($this->isGranted($child, ModuleAction::LIST))
        {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * The action exists on the module and is not disabled (rights aside).
   *
   * @param ModuleDefinition $definition
   * @param string           $action
   *
   * @return bool
   */
  public static function isAvailable(ModuleDefinition $definition, string $action): bool
  {
    $action = ModuleAction::normalize($action);
    if (ModuleAction::LIST === $action)
    {
      return true;
    }
    if ($definition->isDisabled($action))
    {
      return false;
    }
    if ($definition->isPage())
    {
      return \array_key_exists($action, $definition->getActions());
    }
    if (!$definition->isEntity())
    {
      return false;
    }

    return match ($action)
    {
      ModuleAction::CREATE, ModuleAction::EDIT, ModuleAction::DUPLICATE, ModuleAction::DELETE, ModuleAction::TOGGLE => true,
      ModuleAction::TRUNCATE => $definition->isTruncateEnabled(),
      ModuleAction::SORT => null !== $definition->getSortableField(),
      ModuleAction::EXPORT => [] !== $definition->getDownloadFormats(),
      default => \array_key_exists($action, $definition->getExtendActions()),
    };
  }
}
