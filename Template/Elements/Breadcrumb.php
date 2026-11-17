<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Template\Elements;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;

/**
 * Austral Breadcrumb.
 * @template T of object
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Breadcrumb
{

  /**
   * @var array<int, array{url: string|null, entitled?: string, translateKey?: string, picto: string}>
   */
  protected array $breadcrumb = array();

  /**
   * Breadcrumb constructor.
   */
  public function __construct()
  {

  }

  /**
   * @param Modules<T> $modules
   * @param Module<T> $currentModule
   *
   * @return $this
   */
  public function init(Modules $modules, Module $currentModule): self
  {
    $breadcrumb = array();
    $index = 0;
    $moduleIndex = $modules->getModuleByPath("austral_admin_index");
    if($moduleIndex)
    {
      $this->breadcrumbElement($breadcrumb, $moduleIndex, $index);
    }
    if($currentModule->getModulePath() !== "austral_admin_index")
    {
      $this->breadcrumbElement($breadcrumb, $currentModule, $index);
    }
    $this->breadcrumb = $breadcrumb;
    return $this;
  }

  /**
   * @param array<array{url: string, entitled: string, picto: string}> $breadcrumb
   * @param Module<T> $module
   * @param int $index
   */
  protected function breadcrumbElement(array &$breadcrumb, Module $module, int &$index = 0): void
  {
    if($parent = $module->getParent())
    {
      $this->breadcrumbElement($breadcrumb, $parent, $index);
    }
    $breadcrumb[$index] = array(
      "url"         =>  $module->generateUrl(),
      "entitled"    =>  $module->translatePlural(),
      "picto"       =>  $module->getPicto()
    );
    $index++;
  }

  /**
   * @param Module<T> $module
   * @param string $translateKey
   *
   * @return $this
   */
  public function addBreadcrumbEntry(Module $module, string $translateKey): self
  {
    $this->breadcrumb[] = array(
      "url"             =>  $module->generateUrl(),
      "translateKey"    =>  $translateKey,
      "picto"           =>  $module->getPicto()
    );
    return $this;
  }

  /**
   * get
   *
   * @return array<int, array{url: string|null, entitled?: string, translateKey?: string, picto: string}>
   */
  public function get(): array
  {
    return $this->breadcrumb;
  }

}