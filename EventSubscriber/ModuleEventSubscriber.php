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
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\HttpBundle\Mapping\DomainFilterMapping;
use Austral\HttpBundle\Services\DomainsManagement;
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
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var TranslatorInterface
   */
  protected TranslatorInterface $translator;

  /**
   * @var DomainsManagement
   */
  protected DomainsManagement $domainsManagement;

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
   * @param Mapping $mapping
   * @param TranslatorInterface $translator
   * @param DomainsManagement $domainsManagement
   * @param Generator $fileLinkGenerator
   * @param AdminConfiguration $adminConfiguration
   */
  public function __construct(Mapping $mapping,
    TranslatorInterface $translator,
    DomainsManagement $domainsManagement,
    Generator $fileLinkGenerator,
    AdminConfiguration $adminConfiguration)
  {
    $this->mapping = $mapping;
    $this->translator = $translator;
    $this->domainsManagement = $domainsManagement;
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

      /** @var DomainFilterMapping $domainFilterMapping */
      if($domainFilterMapping = $this->mapping->getEntityClassMapping($entityManager->getClass(), DomainFilterMapping::class))
      {
        if($this->domainsManagement->getEnabledDomainWithoutVirtual() && $domainFilterMapping->getAutoDomainId()) {
          $moduleChange = false;
          /** @var DomainInterface $domain */
          foreach($this->domainsManagement->getDomainsWithoutVirtual() as $domain)
          {
            if($domain->getId() !== DomainsManagement::DOMAIN_ID_MASTER)
            {
              if($domainFilterMapping->getForAllDomainEnabled() || $domain->getId() !== DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS)
              {
                $moduleChange = true;
                $moduleEvent->getModules()->generateModuleByDomain(
                  $moduleEvent->getModule()->getModuleKey(),
                  $moduleEvent->getModule()->getModuleParameters(),
                  $domain,
                  $moduleEvent->getModule()
                );
              }
            }
          }
          if($domainFilterMapping->getForAllDomainEnabled())
          {
            $moduleChange = true;
            $moduleEvent->getModules()->generateModuleByDomain(
              $moduleEvent->getModule()->getModuleKey(),
              $moduleEvent->getModule()->getModuleParameters(),
              $this->domainsManagement->getDomainForAll(),
              $moduleEvent->getModule()
            );
          }
          if($moduleChange)
          {
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