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
use Austral\AdminBundle\Template\Interfaces\TemplateParametersInterface;

use Austral\FilterBundle\Mapper\FilterMapper;
use Austral\ListBundle\Mapper\ListMapper;

/**
 * Austral Admin Event List.
 * @template T of object
 *
 * @extends AdminEvent<T>
 * @implements FilterEventInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ListAdminEvent extends AdminEvent implements FilterEventInterface
{

  /** @deprecated  */
  CONST string EVENT_START = "austral.admin.event.list.start";

  /** @deprecated  */
  CONST string EVENT_HYDRATE = "austral.admin.event.list.hydrate";

  /** @deprecated  */
  CONST string EVENT_END = "austral.admin.event.list.end";

  /** @deprecated  */
  CONST string EVENT_DASHBOARD = "austral.admin.event.list.dashboard";

  /**
   * @var ListMapper<T>|null
   */
  private ?ListMapper $listMapper;

  /**
   * @var FilterMapper<T>|null
   */
  private ?FilterMapper $filterMapper;

  /**
   * @var string
   */
  private string $actionName;

  /**
   * @var TemplateParametersInterface
   */
  private TemplateParametersInterface $templateParameters;

  /**
   * @param AdminHandler<T> $adminHandler
   * @param TemplateParametersInterface $templateParameters
   * @param string $actionName
   * @param ListMapper<T>|null $listMapper
   * @param FilterMapper<T>|null $filterMapper
   */
  public function __construct(
    AdminHandler $adminHandler,
    TemplateParametersInterface $templateParameters,
    string $actionName,
    ?ListMapper $listMapper = null,
    ?FilterMapper $filterMapper = null
  )
  {
    parent::__construct($adminHandler);
    $this->templateParameters = $templateParameters;
    $this->actionName = $actionName;
    $this->listMapper = $listMapper;
    $this->filterMapper = $filterMapper;
  }

  /**
   * Get templateParameters
   * @return TemplateParametersInterface
   */
  public function getTemplateParameters(): TemplateParametersInterface
  {
    return $this->templateParameters;
  }

  /**
   * Get actionName
   * @return string|null
   */
  public function getActionName(): ?string
  {
    return $this->actionName;
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
  public function setFilterMapper(FilterMapper $filterMapper): self
  {
    $this->filterMapper = $filterMapper;
    return $this;
  }

}