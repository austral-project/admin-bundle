<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\AdminBundle\Handler\Base;
use Austral\AdminBundle\Template\Interfaces\TemplateParametersInterface;
use Austral\HttpBundle\Handler\HttpHandler;

use Austral\AdminBundle\Handler\Interfaces\AdminHandlerInterface;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;
use Austral\HttpBundle\Template\Interfaces\HttpTemplateParametersInterface;


/**
 * Austral AdminHandler Abstract.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @abstract
 */
abstract class BaseAdminHandler extends HttpHandler implements AdminHandlerInterface
{

  /**
   * @var HttpTemplateParametersInterface|TemplateParametersInterface
   */
  protected HttpTemplateParametersInterface $templateParameters;

  /**
   * @var Module
   */
  protected Module $module;

  /**
   * @var Modules
   */
  protected Modules $modules;

  /**
   * @return Module
   */
  public function getModule(): Module
  {
    return $this->module;
  }

  /**
   * @param Module $module
   *
   * @return $this
   */
  public function setModule(Module $module): BaseAdminHandler
  {
    $this->module = $module;
    return $this;
  }

  /**
   * @return Modules
   */
  public function getModules(): Modules
  {
    return $this->modules;
  }

  /**
   * @param Modules $modules
   *
   * @return $this
   */
  public function setModules(Modules $modules): BaseAdminHandler
  {
    $this->modules = $modules;
    return $this;
  }

}