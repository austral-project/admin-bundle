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

use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Austral\SecurityBundle\EntityManager\RoleEntityManager;

/**
 * Austral RolesModules.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class RolesModules
{
  /**
   * @var Modules
   */
  protected Modules $modules;

  /**
   * @var RoleEntityManager
   */
  protected RoleEntityManager $roleEntityManager;

  /**
   * @var array
   */
  protected array $rolesUser = array();

  /**
   * @var array
   */
  protected array $rolesModules = array();

  /**
   * RolesModule constructor
   *
   * @param Modules $modules
   * @param RoleEntityManager $roleEntityManager
   */
  public function __construct(Modules $modules, RoleEntityManager $roleEntityManager)
  {
    $this->modules = $modules;
    $this->roleEntityManager = $roleEntityManager;
  }

  /**
   * initialise
   *
   * @return RolesModules
   */
  public function initialise(): RolesModules
  {
    $this->rolesUser = $this->roleEntityManager->selectAll("id", "ASC", function(AustralQueryBuilder $australQueryBuilder){
      $australQueryBuilder->indexBy("root", "root.role");
    });

    /** @var Module $module */
    foreach($this->modules->getModules() as $module)
    {
      if(!$module->getFilterDomainId())
      {
        /** @var Module $parent */
        if($parent = $module->getParent())
        {
          if(!$rolesModuleParent = $this->getRoleModuleByModule($parent))
          {
            $rolesModuleParent = $this->generateRolesModule($parent);
          }
          $this->generateRolesModule($module, $rolesModuleParent);
        }
        else
        {
          $this->generateRolesModule($module);
        }
      }
    }
    ksort($this->rolesModules, SORT_NUMERIC);
    return $this;
  }

  /**
   * getRolesModules
   *
   * @return array
   */
  public function getRolesModules(): array
  {
    return $this->rolesModules;
  }

  /**
   * getRoleModule
   *
   * @param Module $module
   * @return RolesModule|null
   */
  public function getRoleModuleByModule(Module $module): ?RolesModule
  {
    return $this->getRoleModuleByModuleKey($module->getModuleKeyWithPosition());
  }

  /**
   * getRoleModule
   *
   * @param string $moduleKeyWithPosition
   * @return RolesModule|null
   */
  public function getRoleModuleByModuleKey(string $moduleKeyWithPosition): ?RolesModule
  {
    return array_key_exists($moduleKeyWithPosition, $this->rolesModules) ? $this->rolesModules[$moduleKeyWithPosition] : null;
  }

  /**
   * generateRolesModule
   *
   * @param Module $module
   * @param RolesModule|null $parent
   * @return RolesModule|null
   */
  protected function generateRolesModule(Module $module, ?RolesModule $parent = null): ?RolesModule
  {
    $roles = array();
    foreach($module->getGrantedByAction() as $grantedName => $enabled)
    {
      if($grantedName == "default")
      {
        if(array_key_exists($module->getSecurityKey(), $this->rolesUser))
        {
          $roles[$module->getSecurityKey()] = "view";
        }
      }
      elseif($grantedName !== "list")
      {
        $roleValue = $module->getSecurityKey()."_".strtoupper($grantedName);
        if(array_key_exists($roleValue, $this->rolesUser))
        {
          $roles[$roleValue] = strtoupper($grantedName);
        }
      }
    }
    $rolesModule = null;
    if($roles)
    {
      if(!$rolesModule = $this->getRoleModuleByModule($module))
      {
        $rolesModule = new RolesModule($module, $parent);
      }
      foreach($roles as $roleValue => $grantedName)
      {
        $rolesModule->addRole($grantedName, $this->rolesUser[$roleValue]);
      }
      if(!$parent)
      {
        $this->rolesModules[$rolesModule->getKeyName()] = $rolesModule;
      }
    }
    return $rolesModule;
  }




}