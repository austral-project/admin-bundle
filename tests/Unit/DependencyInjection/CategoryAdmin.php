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

namespace Austral\AdminBundle\Tests\Unit\DependencyInjection;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Attribute\AsAdminModule;
use Austral\AdminBundle\Event\PageAdminEvent;

/**
 * Two modules declared by attributes: "catalog/category" and "tags".
 */
#[AsAdminModule(parent: 'catalog', entityManager: 'app.entity_manager.page', sortable: true)]
#[AsAdminModule(key: 'tags', entityManager: 'app.entity_manager.page', extendActions: ['publish' => 'publish'], downloadFormats: ['csv'])]
final class CategoryAdmin extends Admin
{
  public function publish(PageAdminEvent $event): void
  {
  }
}
