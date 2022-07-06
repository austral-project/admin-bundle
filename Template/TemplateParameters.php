<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Template;

use Austral\AdminBundle\Configuration\AdminConfiguration;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;
use Austral\AdminBundle\Template\Elements\Breadcrumb;
use Austral\AdminBundle\Template\Elements\Navigation;
use Austral\AdminBundle\Template\Interfaces\TemplateParametersInterface;

use Austral\HttpBundle\Template\HttpTemplateParametersParameters;

/**
 * Austral Template Parameters.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class TemplateParameters extends HttpTemplateParametersParameters implements TemplateParametersInterface
{
  /**
   * @var AdminConfiguration
   */
  protected AdminConfiguration $adminConfiguration;

  /**
   * @var Navigation
   */
  protected Navigation $navigation;

  /**
   * @var Breadcrumb
   */
  protected Breadcrumb $breadcrumb;

  /**
   * @var Modules|null
   */
  protected ?Modules $modules = null;

  /**
   * @var Module|null
   */
  protected ?Module $module = null;

  /**
   * TemplateParameters constructor.
   *
   * @param AdminConfiguration $adminConfiguration
   */
  public function __construct(AdminConfiguration $adminConfiguration)
  {
    parent::__construct();
    $this->adminConfiguration = $adminConfiguration;
    $this->navigation = new Navigation();
    $this->breadcrumb = new Breadcrumb();
  }

  /**
   * @return Navigation
   */
  public function navigation(): Navigation
  {
    return $this->navigation;
  }

  /**
   * @return Breadcrumb
   */
  public function breadcrumb(): Breadcrumb
  {
    return $this->breadcrumb;
  }

  /**
   * @return $this
   */
  public function initTemplate(): TemplateParameters
  {
    if($this->modules && $this->module)
    {
      $this->navigation->init($this->modules);
      $this->breadcrumb->init($this->modules, $this->module);
    }
    return $this;
  }

  /**
   * @return array
   */
  public function __serialize()
  {
    return array_merge($this->parameters, array(
        "config"      =>  array(
          "data_reload_js"   =>  $this->adminConfiguration->getConfig("data_reload_js")
        ),
        "module"      =>  $this->module,
        "navigation"  =>  $this->navigation->__serialize(),
        "breadcrumb"  =>  $this->breadcrumb->__serialize(),
      )
    );
  }

  /**
   * @param Modules $modules
   *
   * @return $this
   */
  public function setModules(Modules $modules): TemplateParameters
  {
    $this->modules = $modules;
    return $this;
  }

  /**
   * @param Module $module
   *
   * @return $this
   */
  public function setModule(Module $module): TemplateParameters
  {
    $this->module = $module;
    return $this;
  }

}