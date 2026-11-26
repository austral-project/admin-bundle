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

namespace Austral\AdminBundle\Tests\Unit\Attribute;

use Austral\AdminBundle\Attribute\AsAdminModule;
use PHPUnit\Framework\TestCase;

final class AsAdminModuleTest extends TestCase
{
  public function testTheKeyComesFromTheClassName(): void
  {
    self::assertSame('category', new AsAdminModule()->getKey('App\Admin\CategoryAdmin'));
    self::assertSame('product-category', new AsAdminModule()->getKey('App\Admin\ProductCategoryAdmin'));
    self::assertSame('news', new AsAdminModule()->getKey('News'));
    self::assertSame('custom', new AsAdminModule(key: 'custom')->getKey('App\Admin\CategoryAdmin'));
  }

  public function testOnlyTheValuesSetAreInTheConfiguration(): void
  {
    self::assertSame(['enabled' => true, 'class' => 'app.admin'], new AsAdminModule()->toConfig('app.admin'));
    self::assertSame(
      ['enabled' => true, 'class' => 'app.admin', 'entity_manager' => 'em', 'sortable' => 'rank', 'truncate' => true, 'downloadFormats' => ['csv']],
      new AsAdminModule(entityManager: 'em', sortable: 'rank', truncate: true, downloadFormats: ['csv'])->toConfig('app.admin'),
    );
  }
}
