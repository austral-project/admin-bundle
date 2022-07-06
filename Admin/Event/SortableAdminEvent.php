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
use Austral\EntityBundle\EntityManager\EntityManagerInterface;


/**
 * Austral Admin Event Change Value.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class SortableAdminEvent extends AdminEvent
{

  CONST EVENT_START = "austral.admin.event.sortable.start";
  CONST EVENT_END = "austral.admin.event.sortable.end";

  /**
   * @var EntityManagerInterface
   */
  private EntityManagerInterface $entityManager;

  /**
   * @var array
   */
  private array $positions;

  /**
   * @var null|\Closure
   */
  private ?\Closure $queryBuilder = null;

  /**
   * @var string
   */
  private string $fieldname;


  /**
   * FormAdminEvent constructor.
   *
   * @param AdminHandler $adminHandler
   * @param EntityManagerInterface $entityManager
   * @param array $positions
   * @param string|null $fieldname
   */
  public function __construct(AdminHandler $adminHandler, EntityManagerInterface $entityManager, array $positions = array(), string $fieldname = null)
  {
    parent::__construct($adminHandler);
    $this->entityManager = $entityManager;
    $this->fieldname = $fieldname ? : "position";
    $this->positions = $positions;
  }

  /**
   * @return EntityManagerInterface
   */
  public function getEntityManager(): EntityManagerInterface
  {
    return $this->entityManager;
  }

  /**
   * @return array
   */
  public function getPositions(): array
  {
    return $this->positions;
  }

  /**
   * @param array $positions
   *
   * @return SortableAdminEvent
   */
  public function setPositions(array $positions): SortableAdminEvent
  {
    $this->positions = $positions;
    return $this;
  }

  /**
   * @return string
   */
  public function getFieldname(): string
  {
    return $this->fieldname;
  }

  /**
   * @param mixed|string $fieldname
   *
   * @return SortableAdminEvent
   */
  public function setFieldname($fieldname): SortableAdminEvent
  {
    $this->fieldname = $fieldname;
    return $this;
  }

  /**
   * @return \Closure|null
   */
  public function getQueryBuilder(): ?\Closure
  {
    return $this->queryBuilder;
  }

  /**
   * @param \Closure|null $queryBuilder
   *
   * @return SortableAdminEvent
   */
  public function setQueryBuilder(?\Closure $queryBuilder): SortableAdminEvent
  {
    $this->queryBuilder = $queryBuilder;
    return $this;
  }

}