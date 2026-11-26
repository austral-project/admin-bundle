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

namespace Austral\AdminBundle\Tests\Fixtures\Admin;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Attribute\AsAdminModule;
use Austral\AdminBundle\Event\PageAdminEvent;

/**
 * Page module of the test application declared by its attribute (key "report" from the class name).
 */
#[AsAdminModule(name: 'Reports', actions: ['index' => 'index'], navigation: ['position' => 30])]
final class ReportAdmin extends Admin
{
  public function index(PageAdminEvent $event): void
  {
    $event->addVars(['content' => 'Report of the day']);
  }
}
