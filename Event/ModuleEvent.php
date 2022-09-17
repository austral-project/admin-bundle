<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Event;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Module Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ModuleEvent extends Event
{
  const EVENT_AUSTRAL_MODULE_ADD = "austral.event.module.admin.add";
  const EVENT_AUSTRAL_MODULE_REMOVE = "austral.event.module.admin.remove";

  /**
   * @var Modules
   */
  private Modules $modules;

  /**
   * @var Module
   */
  private Module $module;


  public function __construct(Modules $modules, Module $module)
  {
    $this->modules = $modules;
    $this->module = $module;
  }

  /**
   * @return Modules
   */
  public function getModules(): Modules
  {
    return $this->modules;
  }

  /**
   * @return Module
   */
  public function getModule(): Module
  {
    return $this->module;
  }

}