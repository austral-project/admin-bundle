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

namespace Austral\AdminBundle\Tests\Fixtures\Entity;

use Austral\SecurityBundle\Entity\Group as BaseGroup;
use Austral\SecurityBundle\Repository\GroupRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Group of the test application, as in the Skeleton of security-bundle.
 */
#[ORM\Table(name: 'austral_security_group')]
#[ORM\Entity(repositoryClass: GroupRepository::class)]
class Group extends BaseGroup
{
}
