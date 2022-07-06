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
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ListAdminEvent extends AdminEvent implements FilterEventInterface
{

  CONST EVENT_START = "austral.admin.event.list.start";
  CONST EVENT_HYDRATE = "austral.admin.event.list.hydrate";
  CONST EVENT_END = "austral.admin.event.list.end";
  CONST EVENT_DASHBOARD = "austral.admin.event.list.dashboard";

  /**
   * @var ListMapper|null
   */
  private ?ListMapper $listMapper;

  /**
   * @var FilterMapper|null
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
   * ListAdminEvent constructor.
   *
   * @param AdminHandler $adminHandler
   * @param TemplateParametersInterface $templateParameters
   * @param string $actionName
   * @param ListMapper|null $listMapper
   * @param FilterMapper|null $filterMapper
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
   * Get objects
   * @return ListMapper
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
  public function setListMapper(ListMapper $listMapper): ListAdminEvent
  {
    $this->listMapper = $listMapper;
    return $this;
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
  public function setFilterMapper(?FilterMapper $filterMapper): ListAdminEvent
  {
    $this->filterMapper = $filterMapper;
    return $this;
  }

}