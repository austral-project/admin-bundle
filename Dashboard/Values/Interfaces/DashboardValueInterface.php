<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Dashboard\Values\Interfaces;

use Austral\AdminBundle\Dashboard\DashboardBlock;

/**
 * Austral DashboardValueInterface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
interface DashboardValueInterface
{

  /**
   * @return string
   */
  public function getKeyname(): string;

  /**
   * @return string
   */
  public function getName(): string;

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName(string $name): DashboardValueInterface;

  /**
   * @return int|null
   */
  public function getPosition(): ?int;

  /**
   * @param int|null $position
   *
   * @return $this
   */
  public function setPosition(?int $position): DashboardValueInterface;

  /**
   * @return DashboardBlock
   */
  public function getParent(): DashboardBlock;

  /**
   * @param DashboardBlock $parent
   *
   * @return $this
   */
  public function setParent(DashboardBlock $parent): DashboardValueInterface;

  /**
   * @return array
   */
  public function getTranslateParameters(): array;

  /**
   * @param array $translateParameters
   *
   * @return DashboardValueInterface
   */
  public function setTranslateParameters(array $translateParameters): DashboardValueInterface;

  /**
   * @return bool
   */
  public function getIsTranslatableText(): bool;

  /**
   * @param bool $isTranslatableText
   *
   * @return $this
   */
  public function setIsTranslatableText(bool $isTranslatableText): DashboardValueInterface;

  /**
   * @return bool
   */
  public function getIsTranslatableValue(): bool;

  /**
   * @param bool $isTranslatableValue
   *
   * @return $this
   */
  public function setIsTranslatableValue(bool $isTranslatableValue): DashboardValueInterface;

  /**
   * @return string|null
   */
  public function getType(): ?string;

  /**
   * @param string|null $type
   *
   * @return $this
   */
  public function setType(?string $type): DashboardValueInterface;

  /**
   * @return string|null
   */
  public function getTemplatePath(): ?string;

  /**
   * @param string|null $templatePath
   *
   * @return $this
   */
  public function setTemplatePath(?string $templatePath): DashboardValueInterface;

}