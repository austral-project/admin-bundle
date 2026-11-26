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

use Austral\AdminBundle\Event\AdminEvent;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\FilterAdminEvent;
use Austral\AdminBundle\Event\FormAdminEvent;
use Austral\AdminBundle\Event\ListAdminEvent;
use Austral\AdminBundle\Event\PageAdminEvent;
use Austral\AdminBundle\Event\QueryAdminEvent;

/**
 * Austral Admin.
 *
 * The behaviour of a module: its list, filters, form and query. An Admin is a service tagged "austral.admin"
 * (automatic for the classes of the application extending Admin): it receives its dependencies in its
 * constructor and keeps no state of a request, everything comes with the events.
 *
 * The configure methods are called for the events of DEFAULT_EVENTS. getEvents() adds methods to other events
 * (AdminEvents constants): event => method, [method, priority] or a list of them.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
abstract class Admin
{
  /**
   * @var array<string, string>
   */
  public const array DEFAULT_EVENTS = [
    AdminEvents::QUERY => 'configureQuery',
    AdminEvents::LIST_CONFIGURE => 'configureListMapper',
    AdminEvents::FILTER_CONFIGURE => 'configureFilterMapper',
    AdminEvents::FORM_CONFIGURE => 'configureFormMapper',
  ];

  /**
   * @var array<string, list<string>>|null Methods by event, the highest priority first
   */
  private ?array $listeners = null;

  /**
   * Other methods called for the events of the module.
   *
   * @return array<string, string|array{0: string, 1?: int}|list<array{0: string, 1?: int}>>
   */
  public function getEvents(): array
  {
    return [];
  }

  /**
   * @param string $eventName
   *
   * @return list<string> Methods for this event, the highest priority first
   */
  final public function getListeners(string $eventName): array
  {
    if (null === $this->listeners)
    {
      $byPriority = [];
      foreach (self::DEFAULT_EVENTS as $name => $method)
      {
        $byPriority[$name][0][] = $method;
      }
      foreach ($this->getEvents() as $name => $params)
      {
        foreach (self::normalize($params) as [$method, $priority])
        {
          if (!method_exists($this, $method))
          {
            throw new \LogicException(\sprintf('The method "%s::%s()" given for the event "%s" does not exist.', static::class, $method, $name));
          }
          if (0 === $priority && \in_array($method, $byPriority[$name][0] ?? [], true))
          {
            continue;
          }
          $byPriority[$name][$priority][] = $method;
        }
      }
      $this->listeners = [];
      foreach ($byPriority as $name => $priorities)
      {
        krsort($priorities, \SORT_NUMERIC);
        $this->listeners[$name] = array_merge(...array_values($priorities));
      }
    }

    return $this->listeners[$eventName] ?? [];
  }

  /**
   * Calls the methods of an event, protected ones included, until a method stops the propagation.
   *
   * @param string     $eventName
   * @param AdminEvent $event
   *
   * @return void
   */
  final public function handle(string $eventName, AdminEvent $event): void
  {
    foreach ($this->getListeners($eventName) as $method)
    {
      if ($event->isPropagationStopped())
      {
        return;
      }
      $this->{$method}($event);
    }
  }

  /**
   * Calls the method of an action (action of a page module, extra action of an entity module).
   *
   * @param string         $method
   * @param PageAdminEvent $event
   *
   * @return void
   */
  final public function callAction(string $method, PageAdminEvent $event): void
  {
    if (!method_exists($this, $method))
    {
      throw new \LogicException(\sprintf('The method "%s::%s()" of the action "%s" does not exist.', static::class, $method, $event->getAction()));
    }
    $this->{$method}($event);
  }

  /**
   * Restrictions of the queries of the module (list, single entity, export, truncate).
   *
   * @param QueryAdminEvent $event
   *
   * @return void
   */
  public function configureQuery(QueryAdminEvent $event): void
  {
  }

  /**
   * Columns, actions and sections of the list. Without column, the list shows the entities as strings.
   *
   * @param ListAdminEvent $event
   *
   * @return void
   */
  public function configureListMapper(ListAdminEvent $event): void
  {
  }

  /**
   * Filters of the list.
   *
   * @param FilterAdminEvent $event
   *
   * @return void
   */
  public function configureFilterMapper(FilterAdminEvent $event): void
  {
  }

  /**
   * Fields of the form.
   *
   * @param FormAdminEvent $event
   *
   * @return void
   */
  public function configureFormMapper(FormAdminEvent $event): void
  {
  }

  /**
   * @param string|array<mixed> $params
   *
   * @return list<array{0: string, 1: int}>
   */
  private static function normalize(string|array $params): array
  {
    if (\is_string($params))
    {
      return [[$params, 0]];
    }
    if (isset($params[0]) && \is_array($params[0]))
    {
      $listeners = [];
      foreach ($params as $param)
      {
        $listeners = array_merge($listeners, self::normalize($param));
      }

      return $listeners;
    }
    if (!isset($params[0]) || !\is_string($params[0]))
    {
      throw new \InvalidArgumentException('An event of Admin::getEvents() needs a method name, [method, priority] or a list of them.');
    }

    return [[$params[0], (int) ($params[1] ?? 0)]];
  }
}
