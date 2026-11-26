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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Admin Event.
 *
 * Base of the events of a module.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
abstract class AdminEvent extends Event
{
  /**
   * @param Module  $module
   * @param Request $request
   */
  public function __construct(protected readonly Module $module, protected readonly Request $request)
  {
  }

  /**
   * @return Module
   */
  public function getModule(): Module
  {
    return $this->module;
  }

  /**
   * @return Request
   */
  public function getRequest(): Request
  {
    return $this->request;
  }
}
