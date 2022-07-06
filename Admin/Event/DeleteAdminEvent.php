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
use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral Admin Event Delete.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class DeleteAdminEvent extends AdminEvent
{

  CONST EVENT_START = "austral.admin.event.delete.start";
  CONST EVENT_END = "austral.admin.event.delete.end";

  /**
   * @var EntityInterface
   */
  private EntityInterface $object;


  /**
   * FormAdminEvent constructor.
   *
   * @param AdminHandler $adminHandler
   * @param EntityInterface $object
   */
  public function __construct(AdminHandler $adminHandler, EntityInterface $object)
  {
    parent::__construct($adminHandler);
    $this->object = $object;
  }

  /**
   * Get object
   * @return EntityInterface
   */
  public function getObject(): EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface $object
   *
   * @return DeleteAdminEvent
   */
  public function setObject(EntityInterface $object): DeleteAdminEvent
  {
    $this->object = $object;
    return $this;
  }

}