<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Module;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Security\ModuleAccess;
use Austral\AdminBundle\Security\ModuleRoles;
use Austral\AdminBundle\Variant\ModuleVariant;
use Austral\EntityBundle\EntityManager\EntityManagerORMInterface;

/**
 * Austral Module.
 *
 * A module for a request: its definition, its Admin, its entity manager, the variants chosen in the URL and the
 * language of the content. Built by ModuleFactory; immutable (the "with" methods return a copy).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class Module
{
  /**
   * @param ModuleDefinition                       $definition
   * @param ModuleAccess                           $access
   * @param Admin|null                             $admin
   * @param EntityManagerORMInterface<object>|null $entityManager
   * @param list<ModuleVariant>                    $variants
   * @param string|null                            $language      Language of the content, null for the default one
   */
  public function __construct(
    private readonly ModuleDefinition $definition,
    private readonly ModuleAccess $access,
    private readonly ?Admin $admin = null,
    private readonly ?EntityManagerORMInterface $entityManager = null,
    private readonly array $variants = [],
    private readonly ?string $language = null,
  ) {
  }

  /**
   * @return ModuleDefinition
   */
  public function getDefinition(): ModuleDefinition
  {
    return $this->definition;
  }

  /**
   * @return string
   */
  public function getPath(): string
  {
    return $this->definition->getPath();
  }

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->definition->getName();
  }

  /**
   * @return bool
   */
  public function hasAdmin(): bool
  {
    return null !== $this->admin;
  }

  /**
   * @return Admin
   */
  public function getAdmin(): Admin
  {
    return $this->admin ?? throw new \LogicException(\sprintf('The admin module "%s" has no Admin class.', $this->getPath()));
  }

  /**
   * @return bool
   */
  public function hasEntityManager(): bool
  {
    return null !== $this->entityManager;
  }

  /**
   * @return EntityManagerORMInterface<object>
   */
  public function getEntityManager(): EntityManagerORMInterface
  {
    return $this->entityManager ?? throw new \LogicException(\sprintf('The admin module "%s" has no entity manager.', $this->getPath()));
  }

  /**
   * @return class-string|null
   */
  public function getEntityClass(): ?string
  {
    /** @var class-string|null $class */
    $class = $this->entityManager?->getClass();

    return $class ?? $this->definition->getEntityClass();
  }

  /**
   * @return list<ModuleVariant> Variants chosen in the URL, by level
   */
  public function getVariants(): array
  {
    return $this->variants;
  }

  /**
   * @return list<string>
   */
  public function getVariantKeys(): array
  {
    return array_map(static fn (ModuleVariant $variant): string => $variant->key, $this->variants);
  }

  /**
   * @param int $level
   *
   * @return ModuleVariant|null
   */
  public function getVariant(int $level): ?ModuleVariant
  {
    return $this->variants[$level] ?? null;
  }

  /**
   * @return bool True when every level of variant is chosen (the list can be shown)
   */
  public function hasAllVariants(): bool
  {
    return \count($this->variants) === $this->definition->getVariantLevels();
  }

  /**
   * @param list<ModuleVariant> $variants
   *
   * @return self
   */
  public function withVariants(array $variants): self
  {
    return new self($this->definition, $this->access, $this->admin, $this->entityManager, $variants, $this->language);
  }

  /**
   * @return string|null
   */
  public function getLanguage(): ?string
  {
    return $this->language;
  }

  /**
   * @param string|null $language
   *
   * @return self
   */
  public function withLanguage(?string $language): self
  {
    return new self($this->definition, $this->access, $this->admin, $this->entityManager, $this->variants, $language);
  }

  /**
   * @param string $action
   *
   * @return bool
   */
  public function isGranted(string $action = ModuleAction::LIST): bool
  {
    return $this->access->isGranted($this->definition, $action);
  }

  /**
   * @param string $action
   *
   * @return string
   */
  public function getRole(string $action = ModuleAction::LIST): string
  {
    return ModuleRoles::role($this->definition, $action);
  }
}
