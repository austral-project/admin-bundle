<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Dashboard;

use Austral\AdminBundle\Dashboard\Values\Interfaces\DashboardValueInterface;
use Exception;

/**
 * Austral DashboardBlock.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DashboardBlock
{

  const WIDTH_FULL = "full";
  const WIDTH_MIDDLE = "middle";
  const WIDTH_AUTO = "";

  const TYPE_TILE = "tile";
  const TYPE_ON_OFF = "onOff";
  const TYPE_ACTION = "action";

  /**
   * @var string
   */
  protected string $keyname;

  /**
   * @var string|null
   */
  protected ?string $name = null;

  /**
   * @var bool
   */
  protected bool $viewName = false;

  /**
   * @var bool
   */
  protected bool $isTranslatable = false;

  /**
   * @var bool
   */
  protected bool $withBackground = true;

  /**
   * @var string|null
   */
  protected ?string $width = null;

  /**
   * @var int|null
   */
  protected ?int $position = null;

  /**
   * @var array
   */
  protected array $values = array();

  /**
   * @var DashboardBlock|null
   */
  protected ?DashboardBlock $parent = null;

  /**
   * @var array
   */
  protected array $children = array();

  /**
   * @var string|null
   */
  protected ?string $type = null;

  /**
   * @param string $keyname
   * @param DashboardBlock|null $parent
   */
  public function __construct(string $keyname, ?DashboardBlock $parent = null)
  {
    $this->keyname = $keyname;
    $this->width = self::WIDTH_AUTO;
    if($parent)
    {
      $parent->addChild($this);
    }
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return $this->name ? : $this->keyname;
  }

  /**
   * @return string
   */
  public function getKeyname(): string
  {
    return $this->keyname;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string $name
   *
   * @return DashboardBlock
   */
  public function setName(string $name): DashboardBlock
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsTranslatable(): bool
  {
    return $this->isTranslatable;
  }

  /**
   * @param bool $isTranslatable
   *
   * @return DashboardBlock
   */
  public function setIsTranslatable(bool $isTranslatable): DashboardBlock
  {
    $this->isTranslatable = $isTranslatable;
    return $this;
  }

  /**
   * @param DashboardBlock $children
   *
   * @return DashboardBlock
   */
  public function addChild(DashboardBlock $children): DashboardBlock
  {
    if(!array_key_exists($children->getKeyname(), $this->children))
    {
      if(!$children->getPosition())
      {
        $children->setPosition((count($this->children)+1)*10);
      }
      $this->children[$children->getKeyname()] = $children;
      if(!$children->getParent())
      {
        $children->setParent($this);
      }
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getChildren(): array
  {
    return $this->children;
  }

  /**
   * @return array
   */
  public function getChildrenByPosition(): array
  {
    $childrenByPosition = array();
    foreach($this->children as $child)
    {
      $position = $this->definedPositionByConflict($childrenByPosition, $child->getPosition());
      $childrenByPosition[$position] = $child;
    }
    ksort($childrenByPosition);
    return $childrenByPosition;
  }

  /**
   * @param array $array
   * @param int $position
   *
   * @return int
   */
  protected function definedPositionByConflict(array $array, int $position): int
  {
    if(array_key_exists($position, $array))
    {
      $position = $position+1;
      return $this->definedPositionByConflict($array, $position);
    }
    return $position;
  }

  /**
   * @param string $keyname
   * @param bool $create
   *
   * @return DashboardBlock|null
   * @throws Exception
   */
  public function getChild(string $keyname, $create = true): ?DashboardBlock
  {
    if(array_key_exists($keyname, $this->children))
    {
      return $this->children[$keyname];
    }
    if($create)
    {
      $newBlock = new DashboardBlock($keyname);
      $this->addChild($newBlock);
      return $newBlock;
    }
    throw new Exception("Children {$keyname} not exist in the DashboardBlock {$this->keyname}");
  }

  /**
   * @return array
   */
  public function getValues(): array
  {
    return $this->values;
  }

  /**
   * @return array
   */
  public function getValuesByPosition(): array
  {
    $valuesByPosition = array();
    /** @var DashboardValueInterface $child */
    foreach($this->values as $child)
    {
      $position = $this->definedPositionByConflict($valuesByPosition, $child->getPosition());
      $valuesByPosition[$position] = $child;
    }
    ksort($valuesByPosition);
    return $valuesByPosition;
  }

  /**
   * @param DashboardValueInterface $value
   *
   * @return DashboardBlock
   */
  public function addValue(DashboardValueInterface $value): DashboardBlock
  {
    if(!array_key_exists($value->getKeyname(), $this->values))
    {
      if(!$value->getPosition())
      {
        $value->setPosition((count($this->values)+1)*10);
      }
      $this->values[$value->getKeyname()] = $value;
      $value->setParent($this);
    }
    return $this;
  }

  /**
   * @return DashboardBlock|null
   */
  public function getParent(): ?DashboardBlock
  {
    return $this->parent;
  }

  /**
   * @param DashboardBlock $parent
   *
   * @return $this
   */
  public function setParent(DashboardBlock $parent): DashboardBlock
  {
    $this->parent = $parent;
    $parent->addChild($this);
    return $this;
  }

  /**
   * @return string|null
   */
  public function getWidth(): ?string
  {
    return $this->width;
  }

  /**
   * @param string|null $width
   *
   * @return DashboardBlock
   */
  public function setWidth(?string $width): DashboardBlock
  {
    $this->width = $width;
    return $this;
  }

  /**
   * @return int|null
   */
  public function getPosition(): ?int
  {
    return $this->position;
  }

  /**
   * @param int $position
   *
   * @return $this
   */
  public function setPosition(int $position): DashboardBlock
  {
    $this->position = $position;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsViewName(): bool
  {
    return $this->viewName;
  }

  /**
   * @param bool $viewName
   *
   * @return $this
   */
  public function setViewName(bool $viewName): DashboardBlock
  {
    $this->viewName = $viewName;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsWithBackground(): bool
  {
    return $this->withBackground;
  }

  /**
   * @param bool $withBackground
   *
   * @return $this
   */
  public function setWithBackground(bool $withBackground): DashboardBlock
  {
    $this->withBackground = $withBackground;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getType(): ?string
  {
    return $this->type;
  }

  /**
   * @param string|null $type
   *
   * @return DashboardBlock
   */
  public function setType(?string $type): DashboardBlock
  {
    $this->type = $type;
    return $this;
  }

}