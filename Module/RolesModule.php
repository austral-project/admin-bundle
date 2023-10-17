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

use Austral\SecurityBundle\Entity\Role;

/**
 * Austral RolesModule.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class RolesModule
{

  /**
   * @var string
   */
  protected string $keyname;

  /**
   * @var string
   */
  protected string $moduleKeyname;

  /**
   * @var string
   */
  protected string $moduleName;

  /**
   * @var RolesModule|null
   */
  protected ?RolesModule $parent = null;

  /**
   * @var array
   */
  protected array $children = array();

  /**
   * @var array
   */
  protected array $roles = array();

  /**
   * RolesModule constructor
   *
   * @param Module $module
   * @param RolesModule|null $rolesModule
   */
  public function __construct(Module $module, ?RolesModule $rolesModule = null)
  {
    $this->moduleKeyname = $module->getModuleKey();
    $this->moduleName = $module->translateSingular();
    $this->keyname = $module->getModuleKeyWithPosition();
    $this->parent = $rolesModule;
    $this->parent?->addChild($this);
  }

  /**
   * addChild
   *
   * @param RolesModule $rolesModule
   * @return $this
   */
  public function addChild(RolesModule $rolesModule): RolesModule
  {
    $this->children[$rolesModule->getKeyName()] = $rolesModule;
    ksort($this->children);
    return $this;
  }

  /**
   * getKeyName
   *
   * @return string
   */
  public function getKeyName(): string
  {
    return $this->keyname;
  }

  /**
   * getKeyName
   *
   * @return string
   */
  public function getModuleKeyName(): string
  {
    return $this->moduleKeyname;
  }

  /**
   * getModuleName
   *
   * @return string
   */
  public function getModuleName(): string
  {
    return $this->moduleName;
  }

  /**
   * getParent
   *
   * @return RolesModule|null
   */
  public function getParent(): ?RolesModule
  {
    return $this->parent;
  }

  /**
   * getChildren
   *
   * @return array
   */
  public function getChildren(): array
  {
    return $this->children;
  }

  /**
   * getRoles
   *
   * @return array
   */
  public function getRoles(): array
  {
    return $this->roles;
  }

  /**
   * setRoles
   *
   * @param array $roles
   * @return $this
   */
  public function setRoles(array $roles): RolesModule
  {
    $this->roles = $roles;
    return $this;
  }

  /**
   * getRoles
   *
   * @param string $grantedName
   * @return array
   */
  public function getRoleByGrantedName(string $grantedName): array
  {
    return array_key_exists($grantedName, $this->roles) ? $this->roles[$grantedName] : array();
  }

  /**
   * addRole
   *
   * @param string $grantedName
   * @param Role $role
   * @return $this
   */
  public function addRole(string $grantedName, Role $role): RolesModule
  {
    if(!array_key_exists($grantedName, $this->roles))
    {
      $this->roles[$role->getRole()] = array(
        "name"      =>  $grantedName,
        "object"    =>  $role
      );
    }
    return $this;
  }




}