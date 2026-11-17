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
use Austral\Contracts\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Admin Event Abstract.
 * @template T of object
 *
 * @extends AdminEventInterface<T>
 *
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
   * @var AdminHandler<T>
   */
  private AdminHandler $adminHandler;

  /**
   * @var EntityInterface<T>|null
   */
  protected ?EntityInterface $object = null;

  /**
   * ListAdminEvent constructor.
   *
   * @param AdminHandler<T> $adminHandler
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
   * @return $this
   */
  public function setRequest(Request $request): self
  {
    $this->request = $request;
    return $this;
  }

  /**
   * Get module
   * @return Module<T>
   */
  public function getCurrentModule(): Module
  {
    return $this->adminHandler->getModule();
  }

  /**
   * Get request
   * @return AdminHandler<T>
   */
  public function getAdminHandler(): AdminHandler
  {
    return $this->adminHandler;
  }

  /**
   * Get object
   * @return EntityInterface<T>|null
   */
  public function getObject(): ?EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface<T> $object
   *
   * @return $this
   */
  public function setObject(EntityInterface $object): self
  {
    $this->object = $object;
    return $this;
  }

}