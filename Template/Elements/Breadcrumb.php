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
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Breadcrumb
{

  /**
   * @var array
   */
  protected array $breadcrumb = array();

  /**
   * Breadcrumb constructor.
   */
  public function __construct()
  {

  }

  /**
   * @param Modules $modules
   * @param Module $module
   *
   * @return $this
   */
  public function init(Modules $modules, Module $module): Breadcrumb
  {
    $breadcrumb = array();
    $index = 0;
    $this->breadcrumbElement($breadcrumb, $modules->getModuleByPath("austral_admin_index"), $index);
    if($module)
    {
      if($module->getModulePath() !== "austral_admin_index")
      {
        $this->breadcrumbElement($breadcrumb, $module, $index);
      }
    }
    $this->breadcrumb = $breadcrumb;
    return $this;
  }

  /**
   * @param array $breadcrumb
   * @param Module $module
   * @param int $index
   */
  protected function breadcrumbElement(array &$breadcrumb, Module $module, int &$index = 0)
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
   * @param Module $module
   * @param $translateKey
   *
   * @return $this
   */
  public function addBreadcrumbEntry(Module $module, $translateKey): Breadcrumb
  {
    $this->breadcrumb[] = array(
      "url"             =>  $module->generateUrl(),
      "translateKey"    =>  $translateKey,
      "picto"           =>  $module->getPicto()
    );
    return $this;
  }

  /**
   * @return array
   */
  public function __serialize()
  {
    return $this->breadcrumb;
  }

}