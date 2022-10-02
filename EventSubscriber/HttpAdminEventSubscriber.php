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

use Austral\AdminBundle\Event\AdminHttpEvent;
use Austral\AdminBundle\Handler\Interfaces\AdminHandlerInterface;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;
use Austral\AdminBundle\Template\TemplateParameters;
use Austral\EntityBundle\EntityManager\EntityManagerInterface;
use Austral\HttpBundle\Event\Interfaces\HttpEventInterface;
use Austral\HttpBundle\EventSubscriber\HttpEventSubscriber;
use Austral\HttpBundle\Handler\Interfaces\HttpHandlerInterface;
use Austral\HttpBundle\Template\Interfaces\HttpTemplateParametersInterface;
use Austral\NotifyBundle\Mercure\Mercure;
use Austral\ToolsBundle\AustralTools;
use ErrorException;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Austral Http EventSubscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class HttpAdminEventSubscriber extends HttpEventSubscriber
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
      AdminHttpEvent::EVENT_AUSTRAL_HTTP_REQUEST_INITIALISE     =>  ["onRequestInitialise", 1024],
      AdminHttpEvent::EVENT_AUSTRAL_HTTP_REQUEST                =>  ["onRequest", 1024],
      AdminHttpEvent::EVENT_AUSTRAL_HTTP_CONTROLLER             =>  ["onController", 1024],
      AdminHttpEvent::EVENT_AUSTRAL_HTTP_RESPONSE               =>  ["onResponse", 1024],
    ];
  }

  /**
   * @param HttpEventInterface $httpEvent
   *
   * @return void
   */
  public function onRequestInitialise(HttpEventInterface $httpEvent)
  {
    $currentLocal = $httpEvent->getKernelEvent()->getRequest()->getSession()->get("austral_language_interface");
    if($httpEvent->getKernelEvent()->getRequest()->attributes->has("language"))
    {
      $currentLocal = $httpEvent->getKernelEvent()->getRequest()->attributes->get("language");
    }
    $httpEvent->getHttpRequest()->setLanguage($currentLocal ? : $this->container->getParameter('locale'));

    if(!$httpEvent->getKernelEvent()->getRequest()->attributes->has("language"))
    {
      $httpEvent->getKernelEvent()->getRequest()->attributes->set("language", $httpEvent->getHttpRequest()->getLanguage());
    }
  }

  /**
   * @param HttpEventInterface $httpEvent
   *
   * @return void
   * @throws ErrorException|ReflectionException
   */
  public function onRequest(HttpEventInterface $httpEvent)
  {
    $this->debug->stopWatchStart("austral.admin.http.event.request", "austral.admin.event.subscriber");

    $urlParameterManagement = null;
    if($this->container->has('austral.seo.url_parameter.management')) {
      $urlParameterManagement = $this->container->get('austral.seo.url_parameter.management');
      $urlParameterManagement->initialize();
    }


    /** @var AttributeBagInterface $requestAttributes */
    $requestAttributes = $httpEvent->getKernelEvent()->getRequest()->attributes;

    /** @var string|null  $modulePath */
    $modulePath = $requestAttributes->get('modulePath', null);
    $requestRoute = $requestAttributes->get('_route', null);
    if(!$modulePath && ($requestRoute == "austral_admin_module_index" || $requestRoute == "austral_admin_index_language"))
    {
      $modulePath = "austral_admin_index";
    }
    elseif(!$modulePath)
    {
      $modulePath = $requestRoute;
    }

    $this->debug->stopWatchLap("austral.admin.http.event.request");

    $language = $httpEvent->getKernelEvent()->getRequest()->getLocale();
    if($this->configuration->get('language.enabled_multi') && $requestAttributes->has('language'))
    {
      $language = $requestAttributes->get('language');
      if(!in_array($language, $this->configuration->get('language.list')))
      {
        throw new HttpException(404,
          "The language \"{$requestAttributes->get('language')}\" in not found in list accepted : ".implode(", ", $this->configuration->get('language.list'))
        );
      }
    }

    $this->debug->stopWatchLap("austral.admin.http.event.request");

    /** @var Modules $modules */
    $modules = $this->container->get('austral.admin.modules')
      ->setLanguageDefault($language);

    $modules->setAuthorizationChecker($this->container->get("security.authorization_checker"))->init();

    if(strpos($requestRoute, "austral_admin_my_account") !== false)
    {
      /** @var Module $currentModule */
      $module = $modules->getModuleByKey("user");
      $this->editMyAccount = true;
    }
    else
    {
      /** @var Module $currentModule */
      $module = $modules->getCurrentModule($modulePath);
    }

    if(!$module)
    {
      throw new HttpException(404, "The module not exist with path {$modulePath}.");
    }

    $module->getAdmin()
      ->setContainer($this->container)
      ->setTranslator($this->container->get('translator'));

    $this->debug->stopWatchLap("austral.admin.http.event.request");
    if($filterDomainId = $module->getFilterDomainId())
    {
      $this->domainsManagement->setFilterDomainId($filterDomainId);
    }

    if(AustralTools::usedImplements(get_class($module->getEntityManager()), EntityManagerInterface::class))
    {
      $module->getEntityManager()->setCurrentLanguage($httpEvent->getKernelEvent()->getRequest()->getLocale());
    }

    $rolePrefix = $requestAttributes->get('_role_prefix', null);

    if($requestAttributes->has("actionKey"))
    {
      $actionKey = $requestAttributes->get("actionKey");
      $rolePrefix = $actionKey;
      if(!$module->hasPathActions($actionKey))
      {
        throw new HttpException(404, "The action key {$actionKey} in module {$modulePath} not exist.");
      }
    }

    $this->debug->stopWatchLap("austral.admin.http.event.request");

    /**
     * Check Granted to access module
     */
    if($requestAttributes->get('_granted') && !$this->editMyAccount)
    {
      $accessIsAccepted = false;
      if(strpos($rolePrefix, "|") !== false)
      {
        $rolesPrefix = explode("|", $rolePrefix);
        foreach($rolesPrefix as $rolePrefix)
        {
          if(!$accessIsAccepted)
          {
            $accessIsAccepted = $module->isGranted($rolePrefix);
          }
        }
      }
      else
      {
        $accessIsAccepted = $module->isGranted($rolePrefix);
      }
      if(!$accessIsAccepted)
      {
        throw new AccessDeniedException("Access denied !!! You don't have permissions to access to this module.");
      }
    }

    /** @var HttpTemplateParametersInterface|TemplateParameters $templateParameters */
    $templateParameters = $this->container->get('austral.admin.template');
    $templateParameters->setModules($modules)->setModule($module)->initTemplate();

    /** @var HttpHandlerInterface|AdminHandlerInterface $adminHandler */
    $adminHandler = $this->container->get("austral.admin.handler");
    $adminHandler->setModules($modules)
      ->setModule($module)
      ->setDomainsManagement($this->domainsManagement)
      ->setTemplateParameters($templateParameters);

    $templateParameters->addParameters("user", $adminHandler->getUser());
    if($this->editMyAccount)
    {
      $requestAttributes->set("id", $adminHandler->getUser()->getId());
    }

    if($this->container->has('austral.entity_manager.config')) {
      $templateParameters->addParameters("variables", $this->container->get('austral.entity_manager.config')->selectAll());
    }

    $this->debug->stopWatchLap("austral.admin.http.event.request");

    $templateParameters->addParameters("language", array(
      "current" =>  $language,
      "default" =>  $this->container->getParameter('locale'),
      "list"    =>  $this->configuration->get("language.list")
    ));

    $mercureParameters = array();
    $tabUuid = $httpEvent->getKernelEvent()->getRequest()->headers->get("austral-tab-uuid", Uuid::uuid4()->toString());
    $adminHandler->setUserTabId($tabUuid);
    $mercureParameters["userTabId"] = $tabUuid;
    if($this->container->has('austral.notify.mercure'))
    {
      /** @var Mercure $mercure */
      $mercure = $this->container->get('austral.notify.mercure');
      $mercure->addSubscribe("{$httpEvent->getKernelEvent()->getRequest()->getPathInfo()}");
      $mercure->setUserTabId($tabUuid);
      $mercureParameters["url"] = $mercure->getHub()->getPublicUrl();
      $mercureParameters["subscribes"] = $mercure->getSubscribes();
    }
    $templateParameters->addParameters("mercure", $mercureParameters);
    $httpEvent->setHandler($adminHandler);

    if($urlParameterManagement) {
      $templateParameters->addParameters("nameByKeysLinks", $urlParameterManagement->getNameByKeyLinks());
    }
    $event = $this->debug->stopWatchStop("austral.admin.http.event.request");
  }

  /**
   * @param HttpEventInterface $httpEvent
   *
   * @return void
   */
  public function onController(HttpEventInterface $httpEvent)
  {

  }

  /**
   * @param HttpEventInterface $httpEvent
   *
   * @return void
   */
  public function onResponse(HttpEventInterface $httpEvent)
  {
    $this->debug->stopWatchStart("austral.admin.http.event.response", "austral.admin.event.subscriber");
    $response = $httpEvent->getKernelEvent()->getResponse();
    if($this->editMyAccount && $response instanceof RedirectResponse)
    {
      $response = new RedirectResponse($this->container->get('router')->generate("austral_admin_my_account"));
    }
    $httpEvent->getKernelEvent()->setResponse($response);
    $this->debug->stopWatchStop("austral.admin.http.event.response");
  }

  /**
   * @param HttpEventInterface $httpEvent
   *
   * @return void
   */
  public function onException(HttpEventInterface $httpEvent)
  {

  }


}