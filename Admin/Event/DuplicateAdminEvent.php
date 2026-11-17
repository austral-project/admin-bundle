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
use Austral\Contracts\Translate\TranslateChildInterface;

/**
 * Austral Admin Event Duplicate.
 * @template T of object
 *
 * @extends AdminEvent<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class DuplicateAdminEvent extends AdminEvent
{

  /** @deprecated */
  CONST string EVENT_START = "austral.admin.event.duplicate.start";

  /** @deprecated */
  CONST string EVENT_END = "austral.admin.event.duplicate.end";

  /**
   * @var EntityInterface<T>
   */
  private EntityInterface $sourceObject;

  /**
   * @var EntityInterface<T>|null
   */
  private ?EntityInterface $duplicateObject;

  /**
   * @var bool
   */
  private bool $flush;

  /**
   * @param AdminHandler<T> $adminHandler
   * @param EntityInterface<T> $sourceObject
   * @param EntityInterface<T>|null $duplicateObject
   * @param bool $flush
   */
  public function __construct(AdminHandler $adminHandler, EntityInterface $sourceObject, ?EntityInterface $duplicateObject = null, bool $flush = true)
  {
    parent::__construct($adminHandler);
    $this->sourceObject = $sourceObject;
    $this->duplicateObject = $duplicateObject;
    $this->flush = $flush;
  }

  /**
   * @return EntityInterface<T>
   */
  public function getSourceObject(): EntityInterface
  {
    return $this->sourceObject;
  }

  /**
   * @param EntityInterface<T> $sourceObject
   *
   * @return $this
   */
  public function setSourceObject(EntityInterface $sourceObject): self
  {
    $this->sourceObject = $sourceObject;
    return $this;
  }

  /**
   * @return EntityInterface<T>|null
   */
  public function getDuplicateObject(): ?EntityInterface
  {
    return $this->duplicateObject;
  }

  /**
   * @param EntityInterface<T>|null $duplicateObject
   *
   * @return $this
   */
  public function setDuplicateObject(?EntityInterface $duplicateObject): self
  {
    $this->duplicateObject = $duplicateObject;
    return $this;
  }

  /**
   * @return bool
   */
  public function getFlush(): bool
  {
    return $this->flush;
  }

  /**
   * @param bool $flush
   *
   * @return $this
   */
  public function setFlush(bool $flush): self
  {
    $this->flush = $flush;
    return $this;
  }

}