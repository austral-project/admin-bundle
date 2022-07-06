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
use Austral\EntityBundle\EntityManager\EntityManagerInterface;
use Austral\ToolsBundle\AustralTools;

use ErrorException;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * @var array
   */
  protected array $modules = array();

  /**
   * @var array
   */
  protected array $modulesPathByKey = array();

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
  protected ?string $currentRoute;

  /**
   * @var string|null
   */
  protected ?string $languageDefault = null;

  /**
   * Modules constructor.
   * @param ContainerInterface $container
   * @param RouterInterface $router
   * @param TranslatorInterface $translator
   * @param AdminConfiguration $adminConfiguration
   */
  public function __construct(ContainerInterface $container,
    RouterInterface $router,
    TranslatorInterface $translator,
    AdminConfiguration $adminConfiguration
  )
  {
    $this->container = $container;
    $this->router = $router;
    $this->translator = $translator;
    $this->adminConfiguration = $adminConfiguration;
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
   */
  public function init(): Modules
  {
    foreach($this->adminConfiguration->getConfig('modules') as $moduleKey => $module)
    {
      if($module['enabled'] === true)
      {
        $this->generateModulePath($module["route"], $module, $moduleKey);
      }
    }

    return $this;
  }

  /**
   * @param string $moduleRoute
   * @param array $module
   * @param string $moduleKey
   * @param bool $defaultNavigationEnabled
   * @param int $defaultNavigationPosition
   * @param Module|null $parent
   *
   * @throws ErrorException
   * @throws ReflectionException
   */
  protected function generateModulePath(string $moduleRoute, array $module, string $moduleKey, bool $defaultNavigationEnabled = true, int $defaultNavigationPosition = 0, Module $parent = null)
  {
    $actions = AustralTools::getValueByKey($module, "actions", array());
    $navigation = AustralTools::getValueByKey($module, "navigation", array());
    if((!$actions) || (array_key_exists("entity", $actions) === true))
    {
      $this->addModule($moduleRoute,
        $module,
        $moduleKey,
        "entity",
        AustralTools::getValueByKey($navigation, "enabled", $defaultNavigationEnabled),
        AustralTools::getValueByKey($navigation, "position", $defaultNavigationPosition),
        array(),
        $parent
      );
    }

    if($actions)
    {
      $defaultNavigationPosition = AustralTools::getValueByKey($navigation, "position", $defaultNavigationPosition);
      foreach($actions as $actionKey => $actionName)
      {
        if($actionKey != "entity")
        {
          $moduleRoutes = array();
          if($moduleRoute !== "austral_admin_index" || $actionKey === "index")
          {
            $moduleRoutes[] = $moduleRoute;
          }
          if($actionKey !== "index")
          {
            $moduleRoutes[] = $actionKey;
          }
          $defaultNavigationPosition++;
          $moduleRouteChild = implode("/", $moduleRoutes);
          $this->addModule($moduleRouteChild,
            $module,
            "{$moduleKey}",
            $actionName,
            $actionKey === "index" ? AustralTools::getValueByKey($navigation, "enabled", $defaultNavigationEnabled) : false,
            $defaultNavigationPosition,
            array(),
            $parent
          );
        }
      }
    }
    if($children = AustralTools::getValueByKey($module, "children", array()))
    {
      $defaultNavigationPositionChild = AustralTools::getValueByKey($navigation, "position", $defaultNavigationPosition);
      foreach($children as $childKey => $child)
      {
        $childRoute = AustralTools::getValueByKey($child, "route", $childKey);
        $defaultNavigationPositionChild++;
        $moduleRouteChild = "{$moduleRoute}/{$childRoute}";
        $this->generateModulePath($moduleRouteChild, $child, $childKey, false, $defaultNavigationPositionChild, $this->modules[$moduleRoute]);
        //$this->modules[$moduleRoute] = $parent;

        if($actions = AustralTools::getValueByKey($child, "actions", array()))
        {
          foreach($actions as $actionKey => $actionName)
          {
            if($actionKey !== "index")
            {
              $moduleRoutes = array();
              $moduleRoutes[] = $moduleRouteChild;
              $moduleRoutes[] = $actionKey;
              $moduleRouteChildAction = implode("/", $moduleRoutes);
              $this->modules[$moduleRouteChildAction]->setParent($this->modules[$moduleRoute])
                ->setIsViewParentPage(false);
              $this->modules[$moduleRoute]->addChildren($this->modules[$moduleRouteChildAction]);
            }
          }
        }
      }
    }
  }

  /**
   * @param string $modulePath
   * @param array $moduleParameters
   * @param string $moduleKey
   * @param string|null $actionName
   * @param bool $navigationEnabled
   * @param int $navigationPosition
   * @param array $actionParameters
   * @param Module|null $parent
   *
   * @return $this
   * @throws ErrorException
   * @throws ReflectionException
   */
  protected function addModule(string $modulePath,
    array $moduleParameters,
    string $moduleKey,
    ?string $actionName = null,
    bool $navigationEnabled = false,
    int $navigationPosition = 0,
    array $actionParameters = array(),
    Module $parent = null
  ): Modules
  {

    /**
     * Construct Module
     */
    $module = new Module($this->router, $moduleKey,
      AustralTools::getValueByKey($moduleParameters,
        "class",
        $this->adminConfiguration->getConfig("default_class")
      ),
    );
    $module->setName($moduleParameters['name'])
      ->setPicto($moduleParameters['picto'])
      ->setPictoTile(AustralTools::getValueByKey($moduleParameters, 'pictoTile'))
      ->setDataHydrateClass(AustralTools::getValueByKey($moduleParameters, 'data_hydrate_class', $this->container->getParameter("austral.admin.data_hydrate.class")))
      ->setModulePath($modulePath)
      ->setActionName($actionName)
      ->setIsSortable(AustralTools::getValueByKey($moduleParameters, "sortable", false))
      ->setLanguageDefault($this->languageDefault);

    if($parent)
    {
      $module->setParent($parent);
    }

    /**
     * Init Entity Manager to Module
     */
    if($actionName == "entity")
    {
      $moduleEntityManagerNameDefault = u($moduleKey)->snake()->toString();
      $entityManagerClass = AustralTools::getValueByKey($moduleParameters, "entity_manager",  "austral.entity_manager.{$moduleEntityManagerNameDefault}");
      if(!$this->container->has($entityManagerClass))
      {
        throw new ErrorException("The module {$moduleKey} is entity type, but the entity manager {$entityManagerClass} is not found !!!");
      }
      $entityManager = $this->container->get($entityManagerClass);
      if(!$entityManager instanceof EntityManagerInterface)
      {
        throw new ErrorException("The entity manager \"{$entityManagerClass}\" not implements ".EntityManagerInterface::class);
      }
    }
    else
    {
      $entityManager = $this->container->get('doctrine.orm.entity_manager');
    }
    $module->setEntityManager($entityManager);

    /**
     * Add Area parameteers
     */
    $module->setNavigation(array(
        "enabled"                 =>  $navigationEnabled,
        "position"                =>  $navigationPosition,
      )
    );

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

    /**
     * Init security granted and path
     * @var string $translateKey
     */
    $securityKey = $moduleKey !== "austral_admin_dashboard" ? "ROLE_".strtoupper(u($modulePath)->snake()) : "ROLE_ADMIN_ACCESS";
    $grantedByActionKeys = array();
    if($actionName == "entity")
    {
      $actionsEntites = $this->adminConfiguration->getConfig("actions_entities");
      $disabledActions = AustralTools::getValueByKey($moduleParameters, "disabledActions", array());
      foreach($actionsEntites as $actionKey => $routes)
      {
        $grantedByActionKey = $securityKey.($actionKey != "list" ? "_".strtoupper($actionKey) : "");
        $grantedByActionKeys[$actionKey == "list" ? "default" : $actionKey] = $this->authorizationChecker ? $this->authorizationChecker->isGranted($grantedByActionKey) : false;
        if(in_array($actionKey, $disabledActions))
        {
          $grantedByActionKeys[$actionKey == "list" ? "default" : $actionKey] = false;
        }
      }
      if(array_key_exists("extendActions", $moduleParameters))
      {
        $module->setExtendActions($moduleParameters["extendActions"]);
        foreach($moduleParameters["extendActions"] as $action => $extendAction)
        {
          $grantedByActionKey = $securityKey."_".strtoupper($action);
          $grantedByActionKeys[$action] = $this->authorizationChecker ? $this->authorizationChecker->isGranted($grantedByActionKey) : false;
          $actionsEntites[$action] = array(
            "default"     =>  "austral_admin_module_action_extend",
            "language"    =>  "austral_admin_module_action_extend_language"
          );
        }
      }

      if(array_key_exists("downloadFormats", $moduleParameters))
      {
        $module->setDownloadFormats($moduleParameters['downloadFormats']);
      }

      $module->setPathActions($actionsEntites);
      $module->setEntityTranslateEnabled($this->adminConfiguration->get('language.enabled_multi'));
    }
    else
    {
      if(array_key_exists("role", $actionParameters))
      {
        $securityKey .= "_".strtoupper($actionParameters["role"]);
      }
      $grantedByActionKeys['default'] = $this->authorizationChecker && $this->authorizationChecker->isGranted($securityKey);
    }
    $module->setGrantedByActionKey($grantedByActionKeys);
    $module->setTruncateEnabled(array_key_exists("truncate", $moduleParameters) ? $moduleParameters["truncate"] : false);
    $this->modules[$modulePath] = $module;
    $this->modulesPathByKey[$moduleKey] = $modulePath;
    $entityClassName = ($module->getEntityManager() instanceof EntityManagerInterface)  ? (new \ReflectionClass($module->getEntityManager()->getClass()))->getShortName() : null;
    if($entityClassName)
    {
      $this->modulesByEntityClassname[$entityClassName] = $modulePath;
    }

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
   * @param string $key
   *
   * @return Module|null
   */
  public function getModuleByKey(string $key): ?Module
  {
    return AustralTools::getValueByKey($this->modules, AustralTools::getValueByKey($this->modulesPathByKey, $key, null), null);
  }


  /**
   * @param string $entityClassname
   *
   * @return Module|null
   */
  public function getModuleByEntityClassname(string $entityClassname): ?Module
  {
    return AustralTools::getValueByKey($this->modules, AustralTools::getValueByKey($this->modulesByEntityClassname, $entityClassname, null), null);
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
    foreach($this->modules as $url => $module)
    {
      if($module->navigationEnabled() === true && $module->isGranted())
      {
        $navigation["{$module->navigationPosition()}-{$url}"] = $module;
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