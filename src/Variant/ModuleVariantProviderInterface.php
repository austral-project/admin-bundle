<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Variant;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleDefinition;

/**
 * Austral Module Variant Provider.
 *
 * Gives the variants of the entity modules it supports. A provider is a service tagged
 * "austral.admin.module_variants"; the priority of the tag orders the levels (the highest first): with a domain
 * provider and a category provider, the URL is /products/{domain}/{category}.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface ModuleVariantProviderInterface
{
  /**
   * Called at the compilation of the container, once by entity module: the answer is kept.
   *
   * @param ModuleDefinition $definition Its entity class is known when the entity manager declares "$class", or
   *                                     with the "entity" key of the module
   *
   * @return bool
   */
  public static function supports(ModuleDefinition $definition): bool;

  /**
   * Variants of a level. Called on the requests of the module: keep it cheap (a query, no loop of queries).
   *
   * @param Module              $module  The module, with the variants of the levels before
   * @param list<ModuleVariant> $parents Variants chosen at the levels before
   *
   * @return iterable<ModuleVariant>
   */
  public function variants(Module $module, array $parents): iterable;
}
