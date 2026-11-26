<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\AdminBundle\Tests\Fixtures\Variant;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleDefinition;
use Austral\AdminBundle\Tests\Fixtures\Entity\Page;
use Austral\AdminBundle\Variant\ModuleVariant;
use Austral\AdminBundle\Variant\ModuleVariantProviderInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Variants of the test module "articles": one by category.
 */
final class CategoryVariantProvider implements ModuleVariantProviderInterface
{
  /**
   * Categories given as variants: a test may keep one only.
   *
   * @var array<string, string>
   */
  public static array $categories = ['news' => 'News', 'events' => 'Events'];

  public static function supports(ModuleDefinition $definition): bool
  {
    return Page::class === $definition->getEntityClass() && 'articles' === $definition->getKey();
  }

  public function variants(Module $module, array $parents): iterable
  {
    foreach (self::$categories as $key => $name)
    {
      yield new ModuleVariant(
        $key,
        $name,
        static function (QueryBuilder $queryBuilder, string $alias) use ($key): void {
          $queryBuilder->andWhere("{$alias}.category = :variant_category")->setParameter('variant_category', $key);
        },
        static function (object $page) use ($key): void {
          if ($page instanceof Page)
          {
            $page->setCategory($key);
          }
        },
      );
    }
  }
}
