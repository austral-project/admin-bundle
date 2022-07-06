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

/**
 * Austral DashboardValue .
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Tile extends Action
{

  /**
   * @var int|null
   */
  protected ?int $colorNum = null;

  /**
   * @param string $keyname
   * @param DashboardBlock |null $parent
   */
  public function __construct(string $keyname, ?DashboardBlock $parent = null)
  {
    parent::__construct($keyname, $parent);
    $this->type = "tile";
    $this->templatePath = "@AustralAdmin/Dashboard/Values/tile.html.twig";
  }

  /**
   * @return int|null
   */
  public function getColorNum(): ?int
  {
    return $this->colorNum;
  }

  /**
   * @param int|null $colorNum
   *
   * @return Tile
   */
  public function setColorNum(?int $colorNum): Tile
  {
    $this->colorNum = $colorNum;
    return $this;
  }

}