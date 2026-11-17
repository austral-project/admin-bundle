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
 * @template T of object
 *
 * @extends HttpTemplateParametersParameters<T>
 * @implements TemplateParametersInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class TemplateParameters extends HttpTemplateParametersParameters implements TemplateParametersInterface
{
  /**
   * @var AdminConfiguration
   */
  protected AdminConfiguration $adminConfiguration;

  /**
   * @var Navigation<T>
   */
  protected Navigation $navigation;

  /**
   * @var Breadcrumb<T>
   */
  protected Breadcrumb $breadcrumb;

  /**
   * @var Modules<T>|null
   */
  protected ?Modules $modules = null;

  /**
   * @var Module<T>|null
   */
  protected ?Module $module = null;

  /**
   * TemplateParameters constructor
   *
   * @param AdminConfiguration $adminConfiguration
   */
  public function __construct(AdminConfiguration $adminConfiguration)
  {
    $this->adminConfiguration = $adminConfiguration;
    $this->navigation = new Navigation();
    $this->breadcrumb = new Breadcrumb();
  }

  /**
   * @return Navigation<T>
   */
  public function navigation(): Navigation
  {
    return $this->navigation;
  }

  /**
   * @return Breadcrumb<T>
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
   * @return array<string, mixed>
   */
  public function __serialize(): array
  {
    return array_merge($this->parameters, array(
        "config"      =>  array(
          "data_reload_js"   =>  $this->adminConfiguration->getConfig("data_reload_js")
        ),
        "module"      =>  $this->module,
        "navigation"  =>  $this->navigation->get(),
        "breadcrumb"  =>  $this->breadcrumb->get(),
      )
    );
  }

  /**
   * @param Modules<T> $modules
   *
   * @return $this
   */
  public function setModules(Modules $modules): self
  {
    $this->modules = $modules;
    return $this;
  }

  /**
   * @param Module<T> $module
   *
   * @return $this
   */
  public function setModule(Module $module): self
  {
    $this->module = $module;
    return $this;
  }

}