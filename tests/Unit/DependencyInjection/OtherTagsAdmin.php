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

/**
 * A second attribute for the module "tags".
 */
#[AsAdminModule(key: 'tags', entityManager: 'app.entity_manager.page')]
final class OtherTagsAdmin extends Admin
{
}
