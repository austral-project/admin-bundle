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
 * Austral Admin Event Change Value.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ChangeValueAdminEvent extends AdminEvent
{

  CONST EVENT_START = "austral.admin.event.change_value.start";
  CONST EVENT_END = "austral.admin.event.change_value.end";

  /**
   * @var EntityInterface
   */
  private EntityInterface $object;

  /**
   * @var string
   */
  private string $fieldname;

  /**
   * @var mixed|null
   */
  private $value;


  /**
   * FormAdminEvent constructor.
   * @param AdminHandler $adminHandler
   * @param EntityInterface $object
   * @param $fieldname
   * @param null $value
   */
  public function __construct(AdminHandler $adminHandler, EntityInterface $object, $fieldname, $value = null)
  {
    parent::__construct($adminHandler);
    $this->object = $object;
    $this->fieldname = $fieldname;
    $this->value = $value;
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
   * @return ChangeValueAdminEvent
   */
  public function setObject(EntityInterface $object): ChangeValueAdminEvent
  {
    $this->object = $object;
    return $this;
  }

  /**
   * Get fieldname
   * @return string
   */
  public function getFieldname(): string
  {
    return $this->fieldname;
  }

  /**
   * @param string $fieldname
   *
   * @return ChangeValueAdminEvent
   */
  public function setFieldname(string $fieldname): ChangeValueAdminEvent
  {
    $this->fieldname = $fieldname;
    return $this;
  }

  /**
   * Get value
   * @return mixed
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * @param mixed $value
   *
   * @return ChangeValueAdminEvent
   */
  public function setValue($value): ChangeValueAdminEvent
  {
    $this->value = $value;
    return $this;
  }

}