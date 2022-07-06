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
class ConfigurationCheckerValue
{

  static public string $TYPE_STRING = "string";
  static public string $TYPE_ARRAY = "array";
  static public string $TYPE_NONE = "none";
  static public string $TYPE_ARRAY_WITHOUT_INDEX = "array_without_index";
  static public string $TYPE_CHECKED = "checked";

  static public string $STATUS_SUCCESS = "success";
  static public string $STATUS_ERROR = "error";
  static public string $STATUS_NONE = "";
  static public string $STATUS_VALUE = "string";

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
   * @var string|null
   */
  protected ?string $status = null;

  /**
   * @var string|null
   */
  protected ?string $value = null;

  /**
   * @var array
   */
  protected array $values = array();

  /**
   * @var bool
   */
  protected bool $isTranslatable = false;

  /**
   * @var bool
   */
  protected bool $isTranslatableValue = false;

  /**
   * @var ConfigurationChecker
   */
  protected ConfigurationChecker $parent;

  /**
   * @param string $keyname
   * @param ConfigurationChecker|null $parent
   */
  public function __construct(string $keyname, ?ConfigurationChecker $parent = null)
  {
    $this->keyname = $keyname;
    $this->type = self::$TYPE_STRING;
    $this->status = self::$STATUS_VALUE;
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
   * @return ConfigurationCheckerValue
   */
  public function setName(string $name): ConfigurationCheckerValue
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @param ConfigurationChecker $parent
   *
   * @return $this
   */
  public function setParent(ConfigurationChecker $parent): ConfigurationCheckerValue
  {
    $this->parent = $parent;
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
   * @return ConfigurationCheckerValue
   */
  public function setIsTranslatable(bool $isTranslatable): ConfigurationCheckerValue
  {
    $this->isTranslatable = $isTranslatable;
    return $this;
  }

  /**
   * @return bool
   */
  public function isTranslatableValue(): bool
  {
    return $this->isTranslatableValue;
  }

  /**
   * @param bool $isTranslatableValue
   *
   * @return ConfigurationCheckerValue
   */
  public function setIsTranslatableValue(bool $isTranslatableValue): ConfigurationCheckerValue
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
   * @return ConfigurationCheckerValue
   */
  public function setType(?string $type): ConfigurationCheckerValue
  {
    $this->type = $type;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getStatus(): ?string
  {
    return $this->status;
  }

  /**
   * @param string|null $status
   *
   * @return ConfigurationCheckerValue
   */
  public function setStatus(?string $status): ConfigurationCheckerValue
  {
    $this->status = $status;
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
   * @return ConfigurationCheckerValue
   */
  public function setValue(?string $value): ConfigurationCheckerValue
  {
    $this->value = $value;
    return $this;
  }

  /**
   * @return array
   */
  public function getValues(): array
  {
    return $this->values;
  }

  /**
   * @param array $values
   *
   * @return ConfigurationCheckerValue
   */
  public function setValues(array $values): ConfigurationCheckerValue
  {
    $this->values = $values;
    return $this;
  }

  /**
   * @param string $key
   * @param string $value
   *
   * @return ConfigurationCheckerValue
   */
  public function addValue(string $key, string $value): ConfigurationCheckerValue
  {
    $this->values[$key] = $value;
    return $this;
  }


}