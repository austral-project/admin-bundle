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


use Austral\AdminBundle\Dashboard\DashboardBlock;
use Austral\AdminBundle\Dashboard\Values as DashboardValues;
use Austral\AdminBundle\Event\DashboardEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Austral Dashboard Subscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DashboardSubscriber implements EventSubscriberInterface
{
  /**
   * @var Request|null
   */
  protected ?Request $request;

  /**
   * ConfigurationChecked constructor.
   */
  public function __construct(RequestStack $requestStack)
  {
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array
  {
    return [
      DashboardEvent::EVENT_AUSTRAL_ADMIN_DASHBOARD =>  ["dashboard", 0],
    ];
  }

  /**
   * @param DashboardEvent $dashboardEvent
   *
   * @throws \Exception
   */
  public function dashboard(DashboardEvent $dashboardEvent)
  {
    $dashboardEvent->getDashboardBlock()->getChild("austral_tiles_values")
      ->setWidth(DashboardBlock::WIDTH_MIDDLE)
      ->setWithBackground(false)
      ->setType(DashboardBlock::TYPE_TILE)
      ->setPosition(1);

    $blockConfigurationHttps = new DashboardValues\OnOff("onOff_https");
    $blockConfigurationHttps->setEntitled("dashboard.configuration.https.entitled")
      ->setDescription("dashboard.configuration.https.description")
      ->setIsTranslatableText(true)
      ->setPosition(900)
      ->setIsEnabled($this->request->getPort() === 443);

    $dashboardEvent->getDashboardBlock()->getChild("austral_configuration_values")
      ->setWidth(DashboardBlock::WIDTH_MIDDLE)
      ->setType(DashboardBlock::TYPE_ON_OFF)
      ->addValue($blockConfigurationHttps)
      ->setPosition(2);

    $dashboardEvent->getDashboardBlock()->getChild("austral_actions")
      ->setWidth(DashboardBlock::WIDTH_FULL)
      ->setType(DashboardBlock::TYPE_ACTION)
      ->setPosition(3);

  }

}