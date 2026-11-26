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
use Austral\AdminBundle\Exception\VariantNotFoundException;
use Austral\AdminBundle\Security\ModuleAccess;
use Austral\AdminBundle\Variant\ModuleVariant;
use Austral\AdminBundle\Variant\ModuleVariantProviderInterface;
use Austral\EntityBundle\EntityManager\EntityManagerORMInterface;
use Psr\Container\ContainerInterface;

/**
 * Austral Module Factory.
 *
 * Builds the module of a request: only this one, with its Admin and its entity manager taken from service
 * locators, and the variants of the URL checked against their providers.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleFactory
{
  /**
   * @param ModuleRegistry     $registry
   * @param ModuleAccess       $access
   * @param ContainerInterface $admins           Admin services by id
   * @param ContainerInterface $entityManagers   Entity managers by id
   * @param ContainerInterface $variantProviders Variant providers by id
   */
  public function __construct(
    private readonly ModuleRegistry $registry,
    private readonly ModuleAccess $access,
    private readonly ContainerInterface $admins,
    private readonly ContainerInterface $entityManagers,
    private readonly ContainerInterface $variantProviders,
  ) {
  }

  /**
   * @return ModuleRegistry
   */
  public function getRegistry(): ModuleRegistry
  {
    return $this->registry;
  }

  /**
   * @param string|ModuleDefinition $module      Path or definition
   * @param list<string>            $variantKeys Keys of the URL, by level (fewer than the levels for the tiles)
   * @param string|null             $language
   *
   * @return Module
   *
   * @throws VariantNotFoundException
   */
  public function create(string|ModuleDefinition $module, array $variantKeys = [], ?string $language = null): Module
  {
    $definition = $module instanceof ModuleDefinition ? $module : $this->registry->get($module);
    if (\count($variantKeys) > $definition->getVariantLevels())
    {
      throw new VariantNotFoundException($definition->getPath(), $definition->getVariantLevels(), (string) end($variantKeys));
    }

    $admin = null;
    if (null !== ($adminId = $definition->getAdminId()))
    {
      $admin = $this->admins->get($adminId);
      if (!$admin instanceof Admin)
      {
        throw new \LogicException(\sprintf('The Admin "%s" of the module "%s" must extend %s.', $adminId, $definition->getPath(), Admin::class));
      }
    }
    $entityManager = null;
    if (null !== ($entityManagerId = $definition->getEntityManagerId()))
    {
      $entityManager = $this->entityManagers->get($entityManagerId);
      if (!$entityManager instanceof EntityManagerORMInterface)
      {
        throw new \LogicException(\sprintf('The entity manager "%s" of the module "%s" must implement %s.', $entityManagerId, $definition->getPath(), EntityManagerORMInterface::class));
      }
      if (null !== $language)
      {
        $entityManager->setCurrentLanguage($language);
      }
    }

    $module = new Module($definition, $this->access, $admin, $entityManager, [], $language);
    $variants = [];
    foreach ($variantKeys as $level => $key)
    {
      $variant = null;
      foreach ($this->variants($module, $level) as $candidate)
      {
        if ($candidate->key === $key)
        {
          $variant = $candidate;
          break;
        }
      }
      if (null === $variant)
      {
        throw new VariantNotFoundException($definition->getPath(), $level, $key);
      }
      $variants[] = $variant;
      $module = $module->withVariants($variants);
    }

    return $module;
  }

  /**
   * Variants available at a level, for the variants already chosen in $module.
   *
   * @param Module $module
   * @param int    $level  Defaults to the next level
   *
   * @return list<ModuleVariant>
   */
  public function variants(Module $module, ?int $level = null): array
  {
    $level ??= \count($module->getVariants());
    $providers = $module->getDefinition()->getVariantProviders();
    if (!isset($providers[$level]))
    {
      return [];
    }
    $provider = $this->variantProviders->get($providers[$level]);
    if (!$provider instanceof ModuleVariantProviderInterface)
    {
      throw new \LogicException(\sprintf('The variant provider "%s" must implement %s.', $providers[$level], ModuleVariantProviderInterface::class));
    }
    $parents = \array_slice($module->getVariants(), 0, $level);
    $variants = [];
    foreach ($provider->variants($module->withVariants($parents), $parents) as $variant)
    {
      $variants[] = $variant;
    }

    return $variants;
  }
}
