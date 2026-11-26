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
use Austral\AdminBundle\Event\PageAdminEvent;

/**
 * A page module Admin that is not declared as a service.
 */
final class ToolAdmin extends Admin
{
  public function index(PageAdminEvent $event): void
  {
  }
}
