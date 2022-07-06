<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Admin\Event;

use Austral\AdminBundle\Handler\AdminHandler;
use Austral\AdminBundle\Module\Module;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Admin Event Abstract.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
abstract class AdminEvent implements AdminEventInterface
{

  /**
   * @var Request|null
   */
  private ?Request $request;

  /**
   * @var AdminHandler
   */
  private AdminHandler $adminHandler;

  /**
   * ListAdminEvent constructor.
   *
   * @param AdminHandler $adminHandler
   */
  public function __construct(AdminHandler $adminHandler)
  {
    $this->adminHandler = $adminHandler;
    $this->request = $this->adminHandler->getRequest();
  }

  /**
   * Get request
   * @return Request|null
   */
  public function getRequest(): ?Request
  {
    return $this->request;
  }

  /**
   * @param Request $request
   *
   * @return AdminEvent
   */
  public function setRequest(Request $request): AdminEvent
  {
    $this->request = $request;
    return $this;
  }

  /**
   * Get module
   * @return Module
   */
  public function getCurrentModule(): Module
  {
    return $this->adminHandler->getModule();
  }

  /**
   * Get request
   * @return AdminHandler
   */
  public function getAdminHandler(): AdminHandler
  {
    return $this->adminHandler;
  }

}