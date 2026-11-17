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
use Austral\Contracts\Entity\EntityInterface;

/**
 * Austral Admin Event Delete.
 * @template T of object
 *
 * @extends AdminEvent<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class DeleteAdminEvent extends AdminEvent
{

  /** @deprecated */
  CONST string EVENT_START = "austral.admin.event.delete.start";

  /** @deprecated */
  CONST string EVENT_END = "austral.admin.event.delete.end";

  /**
   * @param AdminHandler<T> $adminHandler
   * @param EntityInterface<T> $object
   */
  public function __construct(AdminHandler $adminHandler, EntityInterface $object)
  {
    parent::__construct($adminHandler);
    $this->object = $object;
  }

  /**
   * Get object
   * @return EntityInterface<T>
   */
  public function getObject(): EntityInterface
  {
    if (!$this->object instanceof EntityInterface) {
      throw new \LogicException('Object is required.');
    }
    return $this->object;
  }

}