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

namespace Austral\AdminBundle\Tests\Unit\Admin;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Event\AdminEvent;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Security\ModuleAccess;
use Austral\AdminBundle\Tests\Unit\ModuleDefinitions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AdminTest extends TestCase
{
  public function testTheMethodsOfAnEventAreCalledByPriority(): void
  {
    $admin = new class extends Admin {
      /** @var list<string> */
      public array $calls = [];

      public function getEvents(): array
      {
        return [
          AdminEvents::QUERY => [['late', -10], ['early', 10]],
          AdminEvents::DELETE_BEFORE => 'beforeDelete',
          AdminEvents::LIST_CONFIGURE => 'configureListMapper',
        ];
      }

      protected function early(AdminEvent $event): void
      {
        $this->calls[] = 'early';
      }

      protected function late(AdminEvent $event): void
      {
        $this->calls[] = 'late';
      }

      protected function beforeDelete(AdminEvent $event): void
      {
        $this->calls[] = 'beforeDelete';
        $event->stopPropagation();
      }
    };

    self::assertSame(['early', 'configureQuery', 'late'], $admin->getListeners(AdminEvents::QUERY));
    self::assertSame(['configureListMapper'], $admin->getListeners(AdminEvents::LIST_CONFIGURE), 'A default method given again is called once.');
    self::assertSame([], $admin->getListeners(AdminEvents::TOGGLE));

    $event = new class(new Module(ModuleDefinitions::entity(), new ModuleAccess($this->createStub(AuthorizationCheckerInterface::class))), new Request()) extends AdminEvent {
    };
    $admin->handle(AdminEvents::DELETE_BEFORE, $event);
    $admin->handle(AdminEvents::DELETE_BEFORE, $event);
    self::assertSame(['beforeDelete'], $admin->calls, 'A stopped event calls no other method.');
  }

  public function testAnUnknownMethodFails(): void
  {
    $admin = new class extends Admin {
      public function getEvents(): array
      {
        return [AdminEvents::QUERY => 'unknown'];
      }
    };

    $this->expectException(\LogicException::class);
    $admin->getListeners(AdminEvents::QUERY);
  }
}
