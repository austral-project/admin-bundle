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
use Austral\FilterBundle\Mapper\FilterMapper;
use Austral\ListBundle\Mapper\ListMapper;


/**
 * Austral Admin Event Download.
 * @template T of object
 *
 * @extends AdminEvent<T>
 * @implements FilterEventInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class DownloadAdminEvent extends AdminEvent implements FilterEventInterface
{
  /** @deprecated */
  CONST string EVENT_START = "austral.admin.event.download.start";

  /** @deprecated */
  CONST string EVENT_END = "austral.admin.event.download.end";

  /**
   * @var ListMapper<T>|null
   */
  private ?ListMapper $listMapper;

  /**
   * @var FilterMapper<T>|null
   */
  private ?FilterMapper $filterMapper = null;

  /**
   * @var string
   */
  private string $filename;


  /**
   * @param AdminHandler<T> $adminHandler
   * @param ListMapper<T>|null $listMapper
   * @param FilterMapper<T>|null $filterMapper
   */
  public function __construct(AdminHandler $adminHandler, ?ListMapper $listMapper = null, ?FilterMapper $filterMapper = null)
  {
    parent::__construct($adminHandler);
    $this->listMapper = $listMapper;
    $this->filterMapper = $filterMapper;
    $this->filename = $this->getCurrentModule()->getName();
  }

  /**
   * @inheritDoc
   */
  public function getListMapper(): ?ListMapper
  {
    return $this->listMapper;
  }

  /**
   * @inheritDoc
   */
  public function setListMapper(ListMapper $listMapper): self
  {
    $this->listMapper = $listMapper;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getFilterMapper(): ?FilterMapper
  {
    return $this->filterMapper;
  }

  /**
   * @inheritDoc
   */
  public function setFilterMapper(?FilterMapper $filterMapper): self
  {
    $this->filterMapper = $filterMapper;
    return $this;
  }

  /**
   * getFilename
   *
   * @return string
   */
  public function getFilename(): string
  {
    return $this->filename;
  }

  /**
   * @param string $filename
   * @return $this
   */
  public function setFilename(string $filename): DownloadAdminEvent
  {
    $this->filename = $filename;
    return $this;
  }

}