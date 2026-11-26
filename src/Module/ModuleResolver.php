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

use Austral\AdminBundle\Exception\ModuleNotFoundException;
use Austral\AdminBundle\Exception\VariantNotFoundException;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Austral Module Resolver.
 *
 * The module of a request (route attributes set by AdminRouteLoader): an unknown module or variant is a 404, an
 * action that is not granted an access denied.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleResolver
{
  /**
   * @param ModuleFactory $factory
   */
  public function __construct(private readonly ModuleFactory $factory)
  {
  }

  /**
   * @param Request     $request
   * @param string|null $action  Action checked, the action of the route by default
   *
   * @return Module
   *
   * @throws NotFoundHttpException
   * @throws AccessDeniedException
   */
  public function resolve(Request $request, ?string $action = null): Module
  {
    $path = $request->attributes->get(AdminRouteLoader::ATTRIBUTE_MODULE);
    if (!\is_string($path))
    {
      throw new NotFoundHttpException('This page is not a page of an admin module.');
    }
    try
    {
      $definition = $this->factory->getRegistry()->get($path);
    }
    catch (ModuleNotFoundException $exception)
    {
      throw new NotFoundHttpException($exception->getMessage(), $exception);
    }

    $action ??= (string) $request->attributes->get(AdminRouteLoader::ATTRIBUTE_ACTION, ModuleAction::LIST);
    $role = $definition->isPage() && ModuleDefinition::PAGE_INDEX === $action ? ModuleAction::LIST : $action;
    if (str_starts_with($role, AdminUrlGenerator::VARIANTS_ROUTE))
    {
      $role = ModuleAction::LIST;
    }

    $variantKeys = [];
    for ($level = 0; $level < $definition->getVariantLevels(); ++$level)
    {
      $key = $request->attributes->get(AdminUrlGenerator::variantParameter($level));
      if (!\is_string($key) || '' === $key)
      {
        break;
      }
      $variantKeys[] = $key;
    }
    $language = $request->attributes->get('language');

    try
    {
      $module = $this->factory->create($definition, $variantKeys, \is_string($language) && '' !== $language ? $language : null);
    }
    catch (VariantNotFoundException $exception)
    {
      throw new NotFoundHttpException($exception->getMessage(), $exception);
    }
    if (!$module->isGranted($role))
    {
      $exception = new AccessDeniedException(\sprintf('The action "%s" of the admin module "%s" is not granted.', $role, $path));
      $exception->setAttributes([$module->getRole($role)]);

      throw $exception;
    }

    return $module;
  }

  /**
   * @return ModuleFactory
   */
  public function getFactory(): ModuleFactory
  {
    return $this->factory;
  }
}
