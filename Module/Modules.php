<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Module;

use Austral\AdminBundle\Configuration\AdminConfiguration;
use Austral\AdminBundle\Event\ModuleEvent;
use Austral\EntityBundle\EntityManager\EntityManagerInterface;
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\ToolsBundle\AustralTools;

use Austral\ToolsBundle\Services\Debug;
use ErrorException;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Symfony\Contracts\Translation\TranslatorInterface;
use function Symfony\Component\String\u;

/**
 * Austral Modules.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Modules
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * @var RouterInterface
   */
  protected RouterInterface $router;

  /**
   * @var TranslatorInterface
   */
  protected TranslatorInterface $translator;

  /**
   * @var AuthorizationCheckerInterface|null
   */
  protected ?AuthorizationCheckerInterface $authorizationChecker = null;

  /**
   * @var AdminConfiguration
   */
  protected AdminConfiguration $adminConfiguration;

  /**
   * @var Debug
   */
  protected Debug $debug;

  /**
   * @var array
   */
  protected array $modulesPathByKey = array();

  /**
   * @var array
   */
  protected array $modules = array();

  /**
   * @var array
   */
  protected array $modulesByEntityClassname = array();

  /**
   * @var array
   */
  protected array $navigation = array();

  /**
   * @var array
   */
  protected array $breadcrumb = array();

  /**
   * @var Module|null
   */
  protected ?Module $currentModule = null;

  /**
   * @var string|null
   */
  protected ?string $currentPath;

  /**
   * @var string|null
   */
  protected ?string $languageDefault = null;

  /**
   * @var bool
   */
  protected bool $dispatchEvent = true;

  /**
   * Modules constructor.
   *
   * @param ContainerInterface $container
   * @param EventDispatcherInterface $eventDispatcher
   * @param RouterInterface $router
   * @param TranslatorInterface $translator
   * @param AdminConfiguration $adminConfiguration
   * @param Debug $debug
   */
  public function __construct(ContainerInterface $container,
    EventDispatcherInterface $eventDispatcher,
    RouterInterface $router,
    TranslatorInterface $translator,
    AdminConfiguration $adminConfiguration,
    Debug $debug
  )
  {
    $this->container = $container;
    $this->eventDispatcher = $eventDispatcher;
    $this->router = $router;
    $this->translator = $translator;
    $this->adminConfiguration = $adminConfiguration;
    $this->debug = $debug;
  }

  /**
   * @param AuthorizationCheckerInterface $authorizationChecker
   *
   * @return $this
   */
  public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): Modules
  {
    $this->authorizationChecker = $authorizationChecker;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getLanguageDefault(): ?string
  {
    return $this->languageDefault;
  }

  /**
   * @param string|null $languageDefault
   *
   * @return Modules
   */
  public function setLanguageDefault(?string $languageDefault): Modules
  {
    $this->languageDefault = $languageDefault;
    return $this;
  }

  /**
   * @return $this
   * @throws ErrorException
   * @throws ReflectionException
   * @throws \Exception
   */
  public function init(): Modules
  {
    $this->debug->stopWatchStart("austral.admin.modules.init", "austral.admin.modules");
    /**
     * @var string $moduleKey
     * @var array $moduleParameters
     */
    foreach($this->adminConfiguration->getConfig('modules') as $moduleKey => $moduleParameters)
    {
      if($moduleParameters['enabled'] === true)
      {
        $this->generateModule($moduleKey, $moduleParameters);
      }
    }
    $this->debug->stopWatchStop("austral.admin.modules.init");
    return $this;
  }

  /**
   * @param string $moduleKey
   * @param array $moduleParameters
   * @param bool $defaultNavigationEnabled
   * @param int $defaultNavigationPosition
   * @param Module|null $parent
   *
   * @throws \Exception
   */
  protected function generateModule(string $moduleKey, array $moduleParameters, bool $defaultNavigationEnabled = true, int $defaultNavigationPosition = 0, Module $parent = null)
  {
    $this->debug->stopWatchStart("austral.admin.modules.generate.module.{$moduleKey}", "austral.admin.modules");
    $actions = AustralTools::getValueByKey($moduleParameters, "actions", array());
    $actionName = null;
    if(count($actions) <= 0 || array_key_exists("entity", $actions) === true)
    {
      $actionName = "entity";
      unset($actions["entity"]);
    }
    elseif(array_key_exists("index", $actions) === true)
    {
      $actionName = $actions["index"];
      unset($actions["index"]);
    }

    $navigation = AustralTools::getValueByKey($moduleParameters, "navigation", array());
    $modulePath = ($parent ? $parent->getModulePath()."/" : null).$moduleParameters["route"];

    $module = $this->createModule($moduleKey, $moduleParameters, $modulePath, $actionName);
    if($parent)
    {
      $module->setParent($parent);
      $parent->addChildren($module);
    }

    /**
     * Add Area parameters
     */
    $module->setNavigation(array(
        "enabled"                 =>  AustralTools::getValueByKey($navigation, "enabled", $defaultNavigationEnabled),
        "position"                =>  AustralTools::getValueByKey($navigation, "position", $defaultNavigationEnabled),
      )
    );

    if(count($actions) > 0)
    {
      $module->setExtendActions($actions);
      foreach($actions as $actionKey => $actionName)
      {
        $moduleAction = $this->createModule("{$moduleKey}_{$actionKey}", $moduleParameters, "{$modulePath}/{$actionKey}", $actionName);
        $moduleAction->setIsViewParentPage(false);
        $moduleAction->setParent($module);
        $module->addChildren($moduleAction);
        $this->modules["{$module->getModulePath()}/{$actionKey}"] = $moduleAction;
      }
    }

    if($children = AustralTools::getValueByKey($moduleParameters, "children", array()))
    {
      $defaultNavigationPositionChild = AustralTools::getValueByKey($navigation, "position", $defaultNavigationPosition);
      /**
       * @var string $childModuleKey
       * @var array $childModuleParameters
       */
      foreach($children as $childModuleKey => $childModuleParameters)
      {
        $defaultNavigationPositionChild++;
        $this->generateModule($childModuleKey, $childModuleParameters, false, $defaultNavigationPositionChild, $module);
      }
    }
    $this->addModule($module, true);
    $this->debug->stopWatchStop("austral.admin.modules.generate.module.{$moduleKey}");
  }

  /**
   * @param string $moduleKey
   * @param array $moduleParameters
   * @param string $modulePath
   * @param string|null $actionName
   *
   * @return Module
   * @throws ErrorException
   */
  public function createModule(string $moduleKey, array $moduleParameters, string $modulePath, ?string $actionName = null): Module
  {
    $this->debug->stopWatchStart("austral.admin.modules.module.create", "austral.admin.modules");
    $actionName = $actionName ?  : "listChildrenModules";
    $module = new Module($this->router, $moduleKey,
      AustralTools::getValueByKey($moduleParameters,
        "class",
        $this->adminConfiguration->getConfig("default_class")
      ),
    );
    $module->setName($moduleParameters['name'])
      ->setActionName($actionName)
      ->setModulePath($modulePath)
      ->setPicto($moduleParameters['picto'])
      ->setPictoTile(AustralTools::getValueByKey($moduleParameters, 'pictoTile'))
      ->setDataHydrateClass(AustralTools::getValueByKey($moduleParameters, 'data_hydrate_class', $this->container->getParameter("austral.admin.data_hydrate.class")))
      ->setIsSortable(AustralTools::getValueByKey($moduleParameters, "sortable", false))
      ->setLanguageDefault($this->languageDefault)
      ->setDisabledActions(AustralTools::getValueByKey($moduleParameters, "disabledActions", array()))
      ->setExtendActions(AustralTools::getValueByKey($moduleParameters, "extendActions", array()))
      ->setDownloadFormats(AustralTools::getValueByKey($moduleParameters, "downloadFormats", array()))
      ->setEnableMultiDomain(AustralTools::getValueByKey($moduleParameters, 'enable_multi_domain', true));

    if((!array_key_exists("translate_disabled", $moduleParameters) || $moduleParameters["translate_disabled"] === false) && !array_key_exists("austral_filter_by_domain", $moduleParameters)) {
      /**
       * Generate Translate Key and init value
       */
      $translateKey = u($modulePath)->camel()->toString();
      $module->setTranslates(array(
          'singular'  =>  $this->trans("pages.names.{$translateKey}.singular"),
          'plural'    =>  $this->trans("pages.names.{$translateKey}.plural"),
          "type"      =>  AustralTools::getValueByKey($moduleParameters, "translate", "default"),
          "key"       =>  $translateKey
        )
      );
    }
    $module->setTruncateEnabled(array_key_exists("truncate", $moduleParameters) ? $moduleParameters["truncate"] : false);

    $entityManager = $this->container->get('doctrine.orm.entity_manager');
    if($module->isEntityModule())
    {
      $entityManagerClass = AustralTools::getValueByKey($moduleParameters, "entity_manager",  "austral.entity_manager.".(u($moduleKey)->snake()->toString()));
      if(!$this->container->has($entityManagerClass))
      {
        throw new ErrorException("The module {$moduleKey} is entity type, but the entity manager {$entityManagerClass} is not found !!!");
      }
      $entityManager = $this->container->get($entityManagerClass);
      if(!$entityManager instanceof EntityManagerInterface)
      {
        throw new ErrorException("The entity manager \"{$entityManagerClass}\" not implements ".EntityManagerInterface::class);
      }
      $moduleParameters["entity_manager"] = $entityManagerClass;

      if($domainId = AustralTools::getValueByKey($moduleParameters, "austral_filter_by_domain"))
      {
        $this->modulesByEntityClassname[$entityManager->getClass()][$domainId] = $module->getModulePath();
      }
      else
      {
        $this->modulesByEntityClassname[$entityManager->getClass()][] = $module->getModulePath();
      }
    }
    $module->setEntityManager($entityManager);

    /**
     * Init security granted and path
     */
    $securityKey = $module->getModuleKey() !== "austral_admin_dashboard" ? "ROLE_".strtoupper(u($module->getModulePath())->snake()) : "ROLE_ADMIN_ACCESS";
    $grantedByActionKeys = array();
    if($module->isEntityModule())
    {
      $actionsEntites = $this->adminConfiguration->getConfig("actions_entities");
      $disabledActions = $module->getDisabledActions();
      foreach($actionsEntites as $actionKey => $routes)
      {
        $grantedByActionKey = $securityKey.($actionKey != "list" ? "_".strtoupper($actionKey) : "");
        $grantedByActionKeys[$actionKey == "list" ? "default" : $actionKey] = $this->authorizationChecker && $this->authorizationChecker->isGranted($grantedByActionKey);
        if(in_array($actionKey, $disabledActions))
        {
          $grantedByActionKeys[$actionKey == "list" ? "default" : $actionKey] = false;
        }
      }
      if($module->getExtendActions())
      {
        foreach($module->getExtendActions() as $action => $extendAction)
        {
          $grantedByActionKey = $securityKey."_".strtoupper($action);
          $grantedByActionKeys[$action] = $this->authorizationChecker && $this->authorizationChecker->isGranted($grantedByActionKey);
          $actionsEntites[$action] = array(
            "default"     =>  "austral_admin_module_action_extend",
            "language"    =>  "austral_admin_module_action_extend_language"
          );
        }
      }
      $module->setPathActions($actionsEntites);
      $module->setEntityTranslateEnabled($this->adminConfiguration->get('language.enabled_multi'));
    }
    else
    {
      if(array_key_exists("role", $module->getActionParameters()))
      {
        $securityKey .= "_".strtoupper($module->getActionParameters()["role"]);
      }
      $grantedByActionKeys['default'] = $this->authorizationChecker && $this->authorizationChecker->isGranted($securityKey);
    }
    $module->setGrantedByActionKey($grantedByActionKeys);
    $module->setModuleParameters($moduleParameters);
    $this->debug->stopWatchStop("austral.admin.modules.module.create");

    return $module;
  }


  /**
   * @param Module $module
   * @param bool $dispatchEvent
   *
   * @return $this
   */
  public function addModule(Module $module, bool $dispatchEvent = false): Modules
  {
    $this->modules[$module->getModulePath()] = $module;
    $this->modulesPathByKey[$module->getModuleKey()] = $module->getModulePath();

    if($dispatchEvent && $this->dispatchEvent) {
      $moduleEvent = new ModuleEvent($this, $module);
      $this->eventDispatcher->dispatch($moduleEvent, ModuleEvent::EVENT_AUSTRAL_MODULE_ADD);
    }
    return $this;
  }

  /**
   * @param Module $module
   * @param bool $dispatchEvent
   *
   * @return Modules
   */
  public function removeModule(Module $module, bool $dispatchEvent = false): Modules
  {
    if($module->getChildren())
    {
      /** @var Module $child */
      foreach($module->getChildren() as $child)
      {
        $this->removeModule($child);
      }
    }
    if($module->getParent())
    {
      $module->getParent()->removeChildren($module);
    }
    if(array_key_exists($module->getModulePath(), $this->modules))
    {
      unset($this->modules[$module->getModulePath()]);
    }
    if(array_key_exists($module->getModuleKey(), $this->modules))
    {
      unset($this->modulesPathByKey[$module->getModuleKey()]);
    }

    if($dispatchEvent && $this->dispatchEvent) {
      $moduleEvent = new ModuleEvent($this, $module);
      $this->eventDispatcher->dispatch($moduleEvent, ModuleEvent::EVENT_AUSTRAL_MODULE_REMOVE);
    }
    return $this;
  }


  /**
   * @param string $moduleKey
   * @param array $moduleParameters
   * @param DomainInterface|null $domain
   * @param Module|null $parentModule
   *
   * @return $this
   * @throws \Exception
   */
  public function generateModuleByDomain(string $moduleKey, array $moduleParameters, ?DomainInterface $domain, ?Module $parentModule = null): Modules
  {
    $this->dispatchEvent = false;
    $domainName = $domainImg = "";
    if($domain->getId() !== DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS)
    {
      $moduleKey = "$moduleKey-{$domain->getDomain()}";
      $moduleParameters["route"] = "{$domain->getDomain()}";
      $moduleParameters["name"] = "{$moduleParameters["name"]} - {$domain->getName()}";
      $domainName = $domain->getName();
      $domainImg = $this->container->get('austral.entity_file.link.generator')->image($domain, "favicon");
      $keyTranslate = "ByDomain";
    }
    else
    {
      $moduleKey = "$moduleKey-{$domain->getId()}";
      $moduleParameters["route"] = $domain->getId();
      $moduleParameters["name"] = "{$moduleParameters["name"]} - For All Domains";
      $keyTranslate = "ForAllDomain";
    }

    $moduleParameters["austral_filter_by_domain"] = $domain->getId();
    $this->generateModule($moduleKey, $moduleParameters, false, 0, $parentModule);
    $module = $this->getModuleByKey($moduleKey);

    $module->setTranslates(array(
      'singular'  =>  $this->trans("pages.names.{$parentModule->translateKey()}{$keyTranslate}.singular", array('%domainName%' => $domainName)),
      'plural'    =>  $this->trans("pages.names.{$parentModule->translateKey()}{$keyTranslate}.plural", array('%domainName%' => $domainName)),
      "type"      =>  AustralTools::getValueByKey($parentModule->getParameters(), "translate", "default"),
      "key"       =>  "{$parentModule->translateKey()}{$keyTranslate}"
    ));

    // TODO Add count pages if module parent is enabled
    $countPages = null;
    if($module->isEntityModule())
    {
      $module->setQueryBuilder(clone $parentModule->getQueryBuilder());
      $module->getQueryBuilder()
        ->andWhere("root.domainId = :domainId")
        ->setParameter("domainId", $domain->getId());
      //$countPages = $module->getEntityManager()->countByQueryBuilder(clone $module->getQueryBuilder());
    }
    $module->addParameters("austral_filter_by_domain", $domain->getId());
    $module->addParameters("tile", array(
      //"subEntitled"   =>  $this->trans("pages.names.{$parentModule->translateKey()}ByDomain.subTitle", array('%count%'=>$countPages)),
      "img"           =>  $domainImg
    ));

    /** @var Module $child */
    foreach ($module->getChildren() as $child)
    {
      $child->addParameters("austral_filter_by_domain", $domain->getId());
      $child->addParameters("tile", array(
        //"subEntitled"   =>  $this->trans("pages.names.{$parentModule->translateKey()}ByDomain.subTitle", array('%count%'=>$countPages)),
        "img"           =>  $domainImg
      ));
      $keyTranslateAction = ucfirst($child->getActionName());
      $child->setTranslates(array(
        'singular'  =>  $this->trans("pages.names.{$parentModule->translateKey()}{$keyTranslate}{$keyTranslateAction}.singular", array('%domainName%' => $domainName)),
        'plural'    =>  $this->trans("pages.names.{$parentModule->translateKey()}{$keyTranslate}{$keyTranslateAction}.plural", array('%domainName%' => $domainName)),
        "type"      =>  AustralTools::getValueByKey($parentModule->getParameters(), "translate", "default"),
        "key"       =>  "{$parentModule->translateKey()}{$keyTranslate}{$keyTranslateAction}"
      ));
    }
    $this->dispatchEvent = true;
    return $this;
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


  /**
   * @param string $path
   *
   * @return Module|null
   */
  public function getModuleByPath(string $path): ?Module
  {
    return AustralTools::getValueByKey($this->modules, $path, null);
  }


  /**
   * @param string $moduleKey
   *
   * @return Module|null
   */
  public function getModuleByKey(string $moduleKey): ?Module
  {
    return $this->getModuleByPath(AustralTools::getValueByKey($this->modulesPathByKey, $moduleKey, null));
  }

  /**
   * @return array
   */
  public function getModulesByEntityClassname(): array
  {
    return $this->modulesByEntityClassname;
  }

  /**
   * @param string $entityClassname
   * @param int|string $key
   *
   * @return Module|null
   */
  public function getModuleByEntityClassname(string $entityClassname, $key = 0): ?Module
  {
    $modulePaths = AustralTools::getValueByKey($this->modulesByEntityClassname, $entityClassname, array());
    if(count($modulePaths) === 1)
    {
      $key = 0;
    }
    if($modulePath = AustralTools::getValueByKey($modulePaths, $key, null))
    {
      return $this->getModuleByPath($modulePath);
    }
    return null;
  }

  /**
   * @param string|null $path
   *
   * @return Module|null
   */
  public function getCurrentModule(string $path = null): ?Module
  {
    return $this->getModuleByPath($path);
  }

  /**
   * @return array
   */
  public function getModules(): array
  {
    return $this->modules;
  }

  /**
   * @return array[]
   */
  public function navigation(): array
  {
    $navigation = array();
    /**
     * @var Module $module
     */
    foreach($this->modules as $module)
    {
      if($module->navigationEnabled() === true && $module->isGranted())
      {
        $navigation["{$module->navigationPosition()}-{$module->getModulePath()}"] = $module;
      }
    }
    ksort($navigation, SORT_NUMERIC);
    return $navigation;
  }

  /**
   * @param Module|null $lastModule
   *
   * @return array
   */
  public function breadcrumb(Module $lastModule = null): array
  {
    if(!$this->breadcrumb)
    {
      $breadcrumb = array();
      if($this->currentModule)
      {
        $breadcrumb[0] = $this->getModuleByPath("austral_admin_index");
        if($this->currentModule->getModulePath() !== "austral_admin_index")
        {
          $this->breadcrumbElement($breadcrumb, $this->currentModule);
        }
        if($lastModule)
        {
          $breadcrumb[] = $lastModule;
        }
      }
      $this->breadcrumb = $breadcrumb;
    }
    return $this->breadcrumb;
  }

  /**
   * @param Module $module
   *
   * @return $this
   */
  public function addBreadcrumbEntry(Module $module): Modules
  {
    $this->breadcrumb[] = $module;
    return $this;
  }

  /**
   * @param array $breadcrumb
   * @param Module $module
   * @param int $index
   */
  protected function breadcrumbElement(array &$breadcrumb, Module $module, int &$index = 0)
  {
    if($parent = $module->getParent())
    {
      $this->breadcrumbElement($breadcrumb, $parent, $index);
    }
    $index++;
    $breadcrumb[$index] = $module;
  }

}