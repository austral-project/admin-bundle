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
class OnOff extends DashboardValue
{

  /**
   * @var string|null
   */
  protected ?string $entitled = null;

  /**
   * @var string|null
   */
  protected ?string $description = null;

  /**
   * @var bool
   */
  protected bool $isEnabled = false;

  /**
   * @param string $keyname
   * @param DashboardBlock |null $parent
   */
  public function __construct(string $keyname, ?DashboardBlock $parent = null)
  {
    parent::__construct($keyname, $parent);
    $this->type = "switchValue";
    $this->templatePath = "@AustralAdmin/Dashboard/Values/onOff.html.twig";
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
   * @return OnOff
   */
  public function setEntitled(?string $entitled): OnOff
  {
    $this->entitled = $entitled;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getDescription(): ?string
  {
    return $this->description;
  }

  /**
   * @param string|null $description
   *
   * @return OnOff
   */
  public function setDescription(?string $description): OnOff
  {
    $this->description = $description;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsEnabled(): bool
  {
    return $this->isEnabled;
  }

  /**
   * @param bool $isEnabled
   *
   * @return OnOff
   */
  public function setIsEnabled(bool $isEnabled): OnOff
  {
    $this->isEnabled = $isEnabled;
    return $this;
  }

}