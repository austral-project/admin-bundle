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

use Austral\AdminBundle\Configuration\AdminConfiguration;
use Austral\AdminBundle\Event\ModuleEvent;
use Austral\EntityBundle\Entity\Interfaces\FilterByDomainInterface;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\ToolsBundle\AustralTools;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Http EventSubscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ModuleEventSubscriber implements EventSubscriberInterface
{

  /**
   * @var TranslatorInterface
   */
  protected TranslatorInterface $translator;

  /**
   * @var DomainsManagement
   */
  protected DomainsManagement $domains;

  /**
   * @var AdminConfiguration
   */
  protected AdminConfiguration $adminConfiguration;

  /**
   * @var Generator
   */
  protected Generator $fileLinkGenerator;

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
   * @param TranslatorInterface $translator
   * @param DomainsManagement $domains
   * @param Generator $fileLinkGenerator
   * @param AdminConfiguration $adminConfiguration
   *
   */
  public function __construct(TranslatorInterface $translator,
    DomainsManagement $domains,
    Generator $fileLinkGenerator,
    AdminConfiguration $adminConfiguration)
  {
    $this->translator = $translator;
    $this->domains = $domains;
    $this->fileLinkGenerator = $fileLinkGenerator;
    $this->adminConfiguration = $adminConfiguration;
  }

  /**
   * @param ModuleEvent $moduleEvent
   *
   * @throws \Exception
   */
  public function moduleAdd(ModuleEvent $moduleEvent)
  {
    if($moduleEvent->getModule()->isEntityModule() && $moduleEvent->getModule()->getEnableMultiDomain())
    {
      $entityManager = $moduleEvent->getModule()->getEntityManager();
      if(AustralTools::usedImplements($entityManager->getClass(), FilterByDomainInterface::class))
      {
        if($this->domains->getEnabledDomainWithoutVirtual() > 1) {
          $moduleChange = false;
          /** @var DomainInterface $domain */
          foreach($this->domains->getDomainsWithoutVirtual() as $domain)
          {
            $moduleChange = true;
            $moduleEvent->getModules()->generateModuleByDomain(
              $moduleEvent->getModule()->getModuleKey(),
              $moduleEvent->getModule()->getModuleParameters(),
              $domain,
              $moduleEvent->getModule()
            );
          }
          if($moduleChange)
          {
            $moduleEvent->getModules()->generateModuleByDomain(
              $moduleEvent->getModule()->getModuleKey(),
              $moduleEvent->getModule()->getModuleParameters(),
              null,
              $moduleEvent->getModule()
            );
            $moduleEvent->getModule()->setActionName("listChildrenModules");
            $moduleEvent->getModule()->setPathActions(array());
          }
        }
      }
    }
  }

  /**
   * @param $key
   * @param array $parameters
   *
   * @return string
   */
  public function trans($key, array $parameters = array()): string
  {
    return $this->translator->trans($key, $parameters, "austral");
  }


}