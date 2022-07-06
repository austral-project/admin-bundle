<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Configuration;

/**
 * Austral ConfigurationChecker.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ConfigurationChecker
{

  static public string $WIDTH_FULL = "full";
  static public string $WIDTH_AUTO = "";

  /**
   * @var string
   */
  protected string $keyname;

  /**
   * @var string|null
   */
  protected ?string $name = null;

  /**
   * @var string|null
   */
  protected ?string $width = null;

  /**
   * @var int|null
   */
  protected ?int $position = null;

  /**
   * @var string|null
   */
  protected ?string $description = null;

  /**
   * @var bool
   */
  protected bool $isTranslatable = false;

  /**
   * @var array
   */
  protected array $values = array();

  /**
   * @var ConfigurationChecker|null
   */
  protected ?ConfigurationChecker $parent = null;

  /**
   * @var array
   */
  protected array $children = array();

  /**
   * @param string $keyname
   * @param ConfigurationChecker|null $parent
   */
  public function __construct(string $keyname, ?ConfigurationChecker $parent = null)
  {
    $this->keyname = $keyname;
    $this->width = self::$WIDTH_AUTO;
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
   * @return ConfigurationChecker
   */
  public function setName(string $name): ConfigurationChecker
  {
    $this->name = $name;
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
   * @return ConfigurationChecker
   */
  public function setDescription(?string $description): ConfigurationChecker
  {
    $this->description = $description;
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
   * @return ConfigurationChecker
   */
  public function setIsTranslatable(bool $isTranslatable): ConfigurationChecker
  {
    $this->isTranslatable = $isTranslatable;
    return $this;
  }

  /**
   * @param ConfigurationChecker $children
   *
   * @return ConfigurationChecker
   */
  public function addChild(ConfigurationChecker $children): ConfigurationChecker
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
   *
   * @return ConfigurationChecker|null
   * @throws \Exception
   */
  public function getChild(string $keyname): ?ConfigurationChecker
  {
    if(array_key_exists($keyname, $this->children))
    {
      return $this->children[$keyname];
    }
    throw new \Exception("Children {$keyname} not exist in the ConfigurationChecker {$this->keyname}");
  }

  /**
   * @return array
   */
  public function getValues(): array
  {
    return $this->values;
  }

  /**
   * @param ConfigurationCheckerValue $value
   *
   * @return ConfigurationChecker
   */
  public function addValue(ConfigurationCheckerValue $value): ConfigurationChecker
  {
    if(!array_key_exists($value->getKeyname(), $this->values))
    {
      $this->values[$value->getKeyname()] = $value;
      $value->setParent($this);
    }
    return $this;
  }

  /**
   * @return ConfigurationChecker|null
   */
  public function getParent(): ?ConfigurationChecker
  {
    return $this->parent;
  }

  /**
   * @param ConfigurationChecker $parent
   *
   * @return $this
   */
  public function setParent(ConfigurationChecker $parent): ConfigurationChecker
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
   * @return ConfigurationChecker
   */
  public function setWidth(?string $width): ConfigurationChecker
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
  public function setPosition(int $position): ConfigurationChecker
  {
    $this->position = $position;
    return $this;
  }

}