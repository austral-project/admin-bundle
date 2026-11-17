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


/**
 * Austral AdminHandler Abstract.
 * @template T of object
 *
 * @extends HttpHandler<T>
 * @implements AdminHandlerInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @abstract
 */
abstract class BaseAdminHandler extends HttpHandler implements AdminHandlerInterface
{

  /**
   * @var Module<T>|null
   */
  protected ?Module $module = null;

  /**
   * @var Modules<T>|null
   */
  protected ?Modules $modules = null;

  /**
   * @inheritDoc
   */
  public function getModule(): ?Module
  {
    return $this->module;
  }

  /**
   * @inheritDoc
   */
  public function setModule(Module $module): BaseAdminHandler
  {
    $this->module = $module;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getModules(): ?Modules
  {
    return $this->modules;
  }

  /**
   * @inheritDoc
   */
  public function setModules(Modules $modules): BaseAdminHandler
  {
    $this->modules = $modules;
    return $this;
  }

}