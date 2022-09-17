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
use Austral\EntityBundle\Entity\Interfaces\TranslateChildInterface;

/**
 * Austral Admin Event Duplicate.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class DuplicateAdminEvent extends AdminEvent
{

  CONST EVENT_START = "austral.admin.event.duplicate.start";
  CONST EVENT_END = "austral.admin.event.duplicate.end";

  /**
   * @var EntityInterface
   */
  private EntityInterface $sourceObject;

  /**
   * @var EntityInterface|null
   */
  private ?EntityInterface $duplicateObject;

  /**
   * @var bool
   */
  private bool $flush;

  /**
   * @param AdminHandler $adminHandler
   * @param EntityInterface $sourceObject
   * @param EntityInterface|null $duplicateObject
   * @param bool $flush
   */
  public function __construct(AdminHandler $adminHandler, EntityInterface $sourceObject, EntityInterface $duplicateObject = null, bool $flush = true)
  {
    parent::__construct($adminHandler);
    $this->sourceObject = $sourceObject;
    $this->duplicateObject = $duplicateObject;
    $this->flush = $flush;
  }

  /**
   * Get sourceObject
   * @return EntityInterface
   */
  public function getSourceObject(): EntityInterface
  {
    return $this->sourceObject;
  }

  /**
   * @param EntityInterface $sourceObject
   *
   * @return DuplicateAdminEvent
   */
  public function setSourceObject(EntityInterface $sourceObject): DuplicateAdminEvent
  {
    $this->sourceObject = $sourceObject;
    return $this;
  }

  /**
   * Get duplicateObject
   * @return EntityInterface|TranslateChildInterface|null
   */
  public function getDuplicateObject(): ?EntityInterface
  {
    return $this->duplicateObject;
  }

  /**
   * @param EntityInterface|null $duplicateObject
   *
   * @return DuplicateAdminEvent
   */
  public function setDuplicateObject(?EntityInterface $duplicateObject): DuplicateAdminEvent
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
  public function setFlush(bool $flush): DuplicateAdminEvent
  {
    $this->flush = $flush;
    return $this;
  }

}