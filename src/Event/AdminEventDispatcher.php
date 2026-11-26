<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Austral Admin Event Dispatcher.
 *
 * Gives an event of a module to the methods of its Admin, then to the listeners of the application.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminEventDispatcher
{
  /**
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct(private readonly EventDispatcherInterface $dispatcher)
  {
  }

  /**
   * @template T of AdminEvent
   *
   * @param T      $event
   * @param string $eventName One of the AdminEvents constants
   *
   * @return T
   */
  public function dispatch(AdminEvent $event, string $eventName): AdminEvent
  {
    $module = $event->getModule();
    if ($module->hasAdmin())
    {
      $module->getAdmin()->handle($eventName, $event);
    }
    if (!$event->isPropagationStopped())
    {
      $this->dispatcher->dispatch($event, $eventName);
    }

    return $event;
  }

  /**
   * An event that is not of a module (page render, dashboard...).
   *
   * @template T of object
   *
   * @param T      $event
   * @param string $eventName
   *
   * @return T
   */
  public function dispatchGlobal(object $event, string $eventName): object
  {
    return $this->dispatcher->dispatch($event, $eventName);
  }
}
