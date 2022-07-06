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


use Austral\AdminBundle\Configuration\ConfigurationChecker;
use Austral\AdminBundle\Configuration\ConfigurationCheckerValue;
use Austral\AdminBundle\Event\ConfigurationCheckerEvent;
use Austral\ToolsBundle\Services\ServicesStatusChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function Symfony\Component\String\u;

/**
 * Austral ConfigurationChecked Subscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ConfigurationCheckerSubscriber implements EventSubscriberInterface
{

  /**
   * @var ServicesStatusChecker
   */
  protected ServicesStatusChecker $servicesStatusChecker;

  /**
   * ConfigurationChecked constructor.
   */
  public function __construct(ServicesStatusChecker $servicesStatusChecker)
  {
    $this->servicesStatusChecker = $servicesStatusChecker;
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ConfigurationCheckerEvent::EVENT_AUSTRAL_ADMIN_CONFIGURATION_CHECKER =>  ["configurationChecker", 1024],
    ];
  }

  /**
   * @param ConfigurationCheckerEvent $configurationCheckerEvent
   *
   * @throws \Exception
   */
  public function configurationChecker(ConfigurationCheckerEvent $configurationCheckerEvent)
  {
    $configurationChecker = $configurationCheckerEvent->getConfigurationChecker();

    $configurationCheckerServices = $configurationCheckerEvent->addConfiguration("services", $configurationChecker)
      ->setName("configuration.check.services_status_checker.title")
      ->setIsTranslatable(true)
      ->setPosition(1)
      ->setDescription("configuration.check.services_status_checker.description");

    foreach($this->servicesStatusChecker->read() as $service)
    {
      $keyService = u($service['name'])->snake()->toString();
      $configurationCheckerValues = new ConfigurationChecker($keyService);
      $configurationCheckerValues->setName($service['name'])
        ->setIsTranslatable(false)
        ->setWidth(ConfigurationChecker::$WIDTH_FULL)
        ->setParent($configurationCheckerServices);

      $configurationCheckerValue = new ConfigurationCheckerValue("command", $configurationCheckerValues);
      $configurationCheckerValue->setName("configuration.check.services_status_checker.command.entitled")
        ->setIsTranslatable(true)
        ->setType(ConfigurationCheckerValue::$TYPE_STRING)
        ->setValue($service['command']);

      $configurationCheckerValue = new ConfigurationCheckerValue("status", $configurationCheckerValues);
      $configurationCheckerValue->setName("configuration.check.services_status_checker.status.entitled")
        ->setIsTranslatable(true)
        ->setIsTranslatableValue(true)
        ->setType("checked")
        ->setStatus($service['status'] === "run" ? "success" : "")
        ->setValue($service['status'] === "run" ? "configuration.check.choices.enabled" : "configuration.check.choices.disabled");

    }




  }

}