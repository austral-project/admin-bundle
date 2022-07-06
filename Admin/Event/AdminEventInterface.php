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

use Austral\AdminBundle\Handler\Interfaces\AdminHandlerInterface;
use Austral\AdminBundle\Module\Module;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Admin Event Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminEventInterface
{

  /**
   * Get request
   * @return Request|null
   */
  public function getRequest(): ?Request;

  /**
   * @param Request $request
   *
   * @return $this
   */
  public function setRequest(Request $request): AdminEventInterface;

  /**
   * Get module
   * @return Module
   */
  public function getCurrentModule(): Module;

  /**
   * Get request
   * @return AdminHandlerInterface
   */
  public function getAdminHandler(): AdminHandlerInterface;


}