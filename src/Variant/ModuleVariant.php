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

use Doctrine\ORM\QueryBuilder;

/**
 * Austral Module Variant.
 *
 * A filtered version of an entity module, given by a ModuleVariantProviderInterface: the products of a category,
 * the pages of a domain. Its key is a segment of the URL of the module (/products/{category}/...).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleVariant
{
  /**
   * @param string                                      $key        Segment of the URL: letters, digits, "_" and "-"
   * @param string                                      $name       Displayed as it is (a category name is not translated)
   * @param (\Closure(QueryBuilder, string): void)|null $query      Restricts the queries of the module (root alias given)
   * @param (\Closure(object): void)|null               $create     Prepares a new entity (sets its category)
   * @param string|null                                 $picto
   * @param string|null                                 $image      URL of an image for the tile
   * @param array<string, mixed>                        $attributes Free data for the templates and the listeners
   */
  public function __construct(
    public readonly string $key,
    public readonly string $name,
    public readonly ?\Closure $query = null,
    public readonly ?\Closure $create = null,
    public readonly ?string $picto = null,
    public readonly ?string $image = null,
    public readonly array $attributes = [],
  ) {
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $key))
    {
      throw new \InvalidArgumentException(\sprintf('The variant key "%s" must contain only letters, digits, "_" and "-": it is a segment of the URL.', $key));
    }
  }
}
