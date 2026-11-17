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
   * @var string|null
   */
  protected ?string $name = null;

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
   * @var array<string, mixed>
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
   * @var DashboardBlock|null
   */
  protected ?DashboardBlock $parent = null;

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
   * @inheritDoc
   */
  public function getKeyname(): string
  {
    return $this->keyname;
  }

  /**
   * @inheritDoc
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @inheritDoc
   */
  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPosition(): ?int
  {
    return $this->position;
  }

  /**
   * @inheritDoc
   */
  public function setPosition(?int $position): self
  {
    $this->position = $position;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getParent(): ?DashboardBlock
  {
    return $this->parent;
  }

  /**
   * @inheritDoc
   */
  public function setParent(DashboardBlock $parent): self
  {
    $this->parent = $parent;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getTranslateParameters(): array
  {
    return $this->translateParameters;
  }

  /**
   * @inheritDoc
   */
  public function setTranslateParameters(array $translateParameters): self
  {
    $this->translateParameters = $translateParameters;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getIsTranslatableText(): bool
  {
    return $this->isTranslatableText;
  }

  /**
   * @inheritDoc
   */
  public function setIsTranslatableText(bool $isTranslatableText): self
  {
    $this->isTranslatableText = $isTranslatableText;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getIsTranslatableValue(): bool
  {
    return $this->isTranslatableValue;
  }

  /**
   * @inheritDoc
   */
  public function setIsTranslatableValue(bool $isTranslatableValue): self
  {
    $this->isTranslatableValue = $isTranslatableValue;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getType(): ?string
  {
    return $this->type;
  }

  /**
   * @inheritDoc
   */
  public function setType(?string $type): self
  {
    $this->type = $type;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getTemplatePath(): ?string
  {
    return $this->templatePath;
  }

  /**
   * @inheritDoc
   */
  public function setTemplatePath(?string $templatePath): self
  {
    $this->templatePath = $templatePath;
    return $this;
  }

}