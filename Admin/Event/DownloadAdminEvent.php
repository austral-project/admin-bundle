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
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class DownloadAdminEvent extends AdminEvent implements FilterEventInterface
{

  CONST EVENT_START = "austral.admin.event.download.start";
  CONST EVENT_END = "austral.admin.event.download.end";

  /**
   * @var ListMapper|null
   */
  private ListMapper $listMapper;

  /**
   * @var FilterMapper|null
   */
  private ?FilterMapper $filterMapper;

  private string $filename;


  /**
   * FormAdminEvent constructor.
   *
   * @param AdminHandler $adminHandler
   * @param ListMapper|null $listMapper
   * @param FilterMapper|null $filterMapper
   */
  public function __construct(AdminHandler $adminHandler, ListMapper $listMapper = null, ?FilterMapper $filterMapper = null)
  {
    parent::__construct($adminHandler);
    $this->listMapper = $listMapper;
    $this->filterMapper = $filterMapper;
    $this->filename = $this->getCurrentModule()->getName();
  }

  /**
   * @return ListMapper|null
   */
  public function getListMapper(): ?ListMapper
  {
    return $this->listMapper;
  }

  /**
   * @param ?ListMapper $listMapper
   *
   * @return $this
   */
  public function setListMapper(ListMapper $listMapper): DownloadAdminEvent
  {
    $this->listMapper = $listMapper;
    return $this;
  }

  /**
   * @return FilterMapper|null
   */
  public function getFilterMapper(): ?FilterMapper
  {
    return $this->filterMapper;
  }

  /**
   * @param FilterMapper|null $filterMapper
   *
   * @return $this
   */
  public function setFilterMapper(?FilterMapper $filterMapper): DownloadAdminEvent
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