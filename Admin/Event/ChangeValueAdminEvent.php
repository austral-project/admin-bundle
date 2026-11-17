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
 * Austral Admin Event Change Value.
 * @template T of object
 *
 * @extends AdminEvent<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ChangeValueAdminEvent extends AdminEvent
{

  /** @deprecated  */
  CONST string EVENT_START = "austral.admin.event.change_value.start";

  /** @deprecated  */
  CONST string EVENT_END = "austral.admin.event.change_value.end";

  /**
   * @var string
   */
  private string $fieldname;

  /**
   * @var mixed|null
   */
  private mixed $value;


  /**
   * @param AdminHandler<T> $adminHandler
   * @param EntityInterface<T> $object
   * @param string $fieldname
   * @param mixed $value
   */
  public function __construct(AdminHandler $adminHandler, EntityInterface $object, string $fieldname, mixed $value = null)
  {
    parent::__construct($adminHandler);
    $this->object = $object;
    $this->fieldname = $fieldname;
    $this->value = $value;
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
   * @return $this
   */
  public function setFieldname(string $fieldname): self
  {
    $this->fieldname = $fieldname;
    return $this;
  }

  /**
   * Get value
   * @return mixed
   */
  public function getValue(): mixed
  {
    return $this->value;
  }

  /**
   * @param mixed $value
   *
   * @return $this
   */
  public function setValue(mixed $value): self
  {
    $this->value = $value;
    return $this;
  }

}