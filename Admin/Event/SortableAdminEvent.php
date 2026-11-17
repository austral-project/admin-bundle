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
 * @template T of object
 *
 * @extends AdminEvent<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class SortableAdminEvent extends AdminEvent
{

  /** @deprecated  */
  CONST string EVENT_START = "austral.admin.event.sortable.start";

  /** @deprecated  */
  CONST string EVENT_END = "austral.admin.event.sortable.end";

  /**
   * @var EntityManagerInterface<T>
   */
  private EntityManagerInterface $entityManager;

  /**
   * @var array<int, string>
   */
  private array $positions;

  /**
   * @var null|\Closure(object|null): mixed
   */
  private ?\Closure $queryBuilder = null;

  /**
   * @var string
   */
  private string $fieldname;


  /**
   * FormAdminEvent constructor.
   *
   * @param AdminHandler<T> $adminHandler
   * @param EntityManagerInterface<T> $entityManager
   * @param array<int, int> $positions
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
   * @return EntityManagerInterface<T>
   */
  public function getEntityManager(): EntityManagerInterface
  {
    return $this->entityManager;
  }

  /**
   * @return array<int, string>
   */
  public function getPositions(): array
  {
    return $this->positions;
  }

  /**
   * @param array<int, string> $positions
   *
   * @return $this
   */
  public function setPositions(array $positions): self
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
   * @return \Closure(object): mixed|null
   */
  public function getQueryBuilder(): ?\Closure
  {
    return $this->queryBuilder;
  }

  /**
   * @param \Closure|null $queryBuilder
   *
   * @return $this
   */
  public function setQueryBuilder(?\Closure $queryBuilder): self
  {
    $this->queryBuilder = $queryBuilder;
    return $this;
  }

}