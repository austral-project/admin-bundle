<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Page;

use Austral\AdminBundle\Event\AdminEventDispatcher;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\DashboardEvent;
use Austral\AdminBundle\View\AdminView;
use Austral\AdminBundle\View\Navigation;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Austral Dashboard Page.
 *
 * The index of the admin (ROLE_ADMIN_ACCESS): the blocks of AdminEvents::DASHBOARD, the tiles of the modules first.
 * Called by AdminHandler::dashboard().
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class DashboardPage
{
  public const string TEMPLATE = '@AustralAdmin/dashboard.html.twig';
  public const string TEMPLATE_MODULES = '@AustralAdmin/dashboard/modules.html.twig';

  /**
   * __construct
   *
   * @param AuthorizationCheckerInterface $authorizationChecker
   * @param Navigation                    $navigation
   * @param AdminEventDispatcher          $dispatcher
   *
   * @return void
   */
  public function __construct(
    private readonly AuthorizationCheckerInterface $authorizationChecker,
    private readonly Navigation $navigation,
    private readonly AdminEventDispatcher $dispatcher,
  ) {
  }

  /**
   * build
   *
   * @param Request $request
   *
   * @return AdminView
   *
   * @throws AccessDeniedException without ROLE_ADMIN_ACCESS
   */
  public function build(Request $request): AdminView
  {
    if (!$this->authorizationChecker->isGranted(UserInterface::ROLE_ADMIN_ACCESS))
    {
      $exception = new AccessDeniedException('The admin needs the role ROLE_ADMIN_ACCESS.');
      $exception->setAttributes([UserInterface::ROLE_ADMIN_ACCESS]);

      throw $exception;
    }
    $event = new DashboardEvent($request);
    $event->addBlock('modules', self::TEMPLATE_MODULES, ['modules' => \array_slice($this->navigation->build(), 1)], 0);
    $this->dispatcher->dispatchGlobal($event, AdminEvents::DASHBOARD);

    return new AdminView('dashboard', self::TEMPLATE, ['blocks' => $event->getBlocks()]);
  }
}
