<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Dashboard\Values;

use Austral\AdminBundle\Dashboard\DashboardBlock;
use Austral\AdminBundle\Dashboard\Values\Base\DashboardValue;

/**
 * Austral DashboardValue .
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Action extends DashboardValue
{

  /**
   * @var string|null
   */
  protected ?string $entitled = null;

  /**
   * @var string|null
   */
  protected ?string $picto = null;

  /**
   * @var string|null
   */
  protected ?string $value = null;

  /**
   * @var string|null
   */
  protected ?string $url = null;

  /**
   * @var string|null
   */
  protected ?string $routeName = null;

  /**
   * @var array
   */
  protected array $routeParameters = array();

  /**
   * @param string $keyname
   * @param DashboardBlock |null $parent
   */
  public function __construct(string $keyname, ?DashboardBlock $parent = null)
  {
    parent::__construct($keyname, $parent);
    $this->type = "action";
    $this->templatePath = "@AustralAdmin/Dashboard/Values/action.html.twig";
  }

  /**
   * @return string|null
   */
  public function getEntitled(): ?string
  {
    return $this->entitled;
  }

  /**
   * @param string|null $entitled
   *
   * @return Action
   */
  public function setEntitled(?string $entitled): Action
  {
    $this->entitled = $entitled;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getPicto(): ?string
  {
    return $this->picto;
  }

  /**
   * @param string|null $picto
   *
   * @return Action
   */
  public function setPicto(?string $picto): Action
  {
    $this->picto = $picto;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getValue(): ?string
  {
    return $this->value;
  }

  /**
   * @param string|null $value
   *
   * @return Action
   */
  public function setValue(?string $value): Action
  {
    $this->value = $value;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getUrl(): ?string
  {
    return $this->url;
  }

  /**
   * @param string|null $url
   *
   * @return Action
   */
  public function setUrl(?string $url): Action
  {
    $this->url = $url;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getRouteName(): ?string
  {
    return $this->routeName;
  }

  /**
   * @param string|null $routeName
   *
   * @return Action
   */
  public function setRouteName(?string $routeName): Action
  {
    $this->routeName = $routeName;
    return $this;
  }

  /**
   * @return array
   */
  public function getRouteParameters(): ?array
  {
    return $this->routeParameters;
  }

  /**
   * @param array $routeParameters
   *
   * @return Action
   */
  public function setRouteParameters(array $routeParameters): Action
  {
    $this->routeParameters = $routeParameters;
    return $this;
  }

}