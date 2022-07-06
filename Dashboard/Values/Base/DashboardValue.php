<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Dashboard\Values\Base;

use Austral\AdminBundle\Dashboard\Values\Interfaces\DashboardValueInterface;
use Austral\AdminBundle\Dashboard\DashboardBlock;

/**
 * Austral DashboardValue .
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
abstract class DashboardValue implements DashboardValueInterface
{

  /**
   * @var string
   */
  protected string $keyname;

  /**
   * @var string
   */
  protected string $name;

  /**
   * @var string|null
   */
  protected ?string $type = null;

  /**
   * @var int|null
   */
  protected ?int $position = null;

  /**
   * @var string|null
   */
  protected ?string $templatePath = null;

  /**
   * @var array
   */
  protected array $translateParameters = array();

  /**
   * @var bool
   */
  protected bool $isTranslatableText = false;

  /**
   * @var bool
   */
  protected bool $isTranslatableValue = false;

  /**
   * @var DashboardBlock
   */
  protected DashboardBlock $parent;

  /**
   * @param string $keyname
   * @param DashboardBlock |null $parent
   */
  public function __construct(string $keyname, ?DashboardBlock $parent = null)
  {
    $this->keyname = $keyname;
    if($parent)
    {
      $parent->addValue($this);
    }
  }

  /**
   * @return string
   */
  public function getKeyname(): string
  {
    return $this->keyname;
  }

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   *
   * @return DashboardValue
   */
  public function setName(string $name): DashboardValue
  {
    $this->name = $name;
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
   * @param int|null $position
   *
   * @return DashboardValue
   */
  public function setPosition(?int $position): DashboardValue
  {
    $this->position = $position;
    return $this;
  }

  /**
   * @return DashboardBlock
   */
  public function getParent(): DashboardBlock
  {
    return $this->parent;
  }

  /**
   * @param DashboardBlock $parent
   *
   * @return $this
   */
  public function setParent(DashboardBlock $parent): DashboardValue
  {
    $this->parent = $parent;
    return $this;
  }

  /**
   * @return array
   */
  public function getTranslateParameters(): array
  {
    return $this->translateParameters;
  }

  /**
   * @param array $translateParameters
   *
   * @return DashboardValue
   */
  public function setTranslateParameters(array $translateParameters): DashboardValue
  {
    $this->translateParameters = $translateParameters;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsTranslatableText(): bool
  {
    return $this->isTranslatableText;
  }

  /**
   * @param bool $isTranslatableText
   *
   * @return DashboardValue
   */
  public function setIsTranslatableText(bool $isTranslatableText): DashboardValue
  {
    $this->isTranslatableText = $isTranslatableText;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsTranslatableValue(): bool
  {
    return $this->isTranslatableValue;
  }

  /**
   * @param bool $isTranslatableValue
   *
   * @return DashboardValue
   */
  public function setIsTranslatableValue(bool $isTranslatableValue): DashboardValue
  {
    $this->isTranslatableValue = $isTranslatableValue;
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
   * @return DashboardValue
   */
  public function setType(?string $type): DashboardValue
  {
    $this->type = $type;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getTemplatePath(): ?string
  {
    return $this->templatePath;
  }

  /**
   * @param string|null $templatePath
   *
   * @return DashboardValue
   */
  public function setTemplatePath(?string $templatePath): DashboardValue
  {
    $this->templatePath = $templatePath;
    return $this;
  }

}