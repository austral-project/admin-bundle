<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\AdminBundle\EventSubscriber;

use Austral\AdminBundle\Event\ModuleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral Http EventSubscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ModuleEventSubscriber implements EventSubscriberInterface
{

  /**
   * @var bool
   */
  protected bool $editMyAccount = false;

  /**
   * @return array[]
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ModuleEvent::EVENT_AUSTRAL_MODULE_ADD     =>  ["moduleAdd", 1024],
    ];
  }

  /**
   * @param ModuleEvent $moduleEvent
   *
   * @throws \Exception
   */
  public function moduleAdd(ModuleEvent $moduleEvent)
  {

  }


}