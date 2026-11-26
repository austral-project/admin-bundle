<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Admin;

use Austral\AdminBundle\Event\AdminEventDispatcher;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\ConfigurationCheckEvent;
use Austral\AdminBundle\Event\PageAdminEvent;

/**
 * Austral Configuration Admin.
 *
 * Page module "austral_admin_configuration_status": the checks of AdminEvents::CONFIGURATION_CHECK.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ConfigurationAdmin extends Admin
{
  public const string TEMPLATE = '@AustralAdmin/configuration.html.twig';

  /**
   * @param AdminEventDispatcher $dispatcher
   */
  public function __construct(private readonly AdminEventDispatcher $dispatcher)
  {
  }

  /**
   * @param PageAdminEvent $event
   *
   * @return void
   */
  public function index(PageAdminEvent $event): void
  {
    $checks = $this->dispatcher->dispatchGlobal(new ConfigurationCheckEvent($event->getRequest()), AdminEvents::CONFIGURATION_CHECK);
    $event->setTemplate(self::TEMPLATE)->addVars(['checks' => $checks->getChecksByCategory()]);
  }
}
