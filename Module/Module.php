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

use Austral\AdminBundle\Admin\AdminInterface;
use Austral\AdminBundle\Admin\AdminModuleInterface;

use Austral\EntityBundle\EntityManager\EntityManagerInterface;

use Austral\ListBundle\Model\ModuleInterface;
use Austral\ToolsBundle\AustralTools;

use Austral\EntityTranslateBundle\Entity\Interfaces\EntityTranslateMasterInterface;
use Doctrine\ORM\EntityManagerInterface as DoctrineEntityManagerInterface;

use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Austral Module.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Module implements ModuleInterface
{

  /**
   * @var RouterInterface
   */
  protected RouterInterface $router;

  /**
   * @var AdminInterface|AdminModuleInterface
   */
  protected $admin = null;

  /**
   * @var EntityManagerInterface|DoctrineEntityManagerInterface|null
   */
  protected $entityManager = null;

  /**
   * @var Module|null
   */
  protected ?Module $parent = null;

  /**
   * @var string
   */
  protected string $moduleKey;

  /**
   * @var array
   */
  protected array $children = array();

  /**
   * @var array
   */
  protected array $navigation = array(
    "enabled"   =>  false,
    "position"  =>  0
  );

  /**
   * @var string
   */
  protected string $name;

  /**
   * @var string
   */
  protected string $picto;

  /**
   * @var string|null
   */
  protected ?string $pictoTile = null;

  /**
   * @var string
   */
  protected string $modulePath;

  /**
   * @var array
   */
  protected array $grantedByActionKeys = array();

  /**
   * @var array
   */
  protected array $extendActions = array();

  /**
   * @var bool
   */
  protected bool $entityTranslateEnabled = false;

  /**
   * @var bool
   */
  protected bool $truncateEnabled = false;

  /**
   * @var array
   */
  protected array $parameters = array();

  /**
   * @var array
   */
  protected array $translates = array(
    "singular"    =>  "",
    "plural"      =>  "",
    "key"         =>  "",
    "type"        =>  ""
  );

  /**
   * @var string|null
   */
  protected ?string $actionName;

  /**
   * @var array
   */
  protected array $pathActions = array();

  /**
   * @var array
   */
  protected array $downloadFormats = array();

  /**
   * @var string|null
   */
  protected ?string $languageDefault = null;

  /**
   * @var array
   */
  protected array $languages = array();

  /**
   * @var bool
   */
  protected bool $isSortable = false;

  /**
   * @var bool
   */
  protected bool $isViewParentPage = true;

  /**
   * @var string|null
   */
  protected string $dataHydrateClass;

  /**
   * Module constructor.
   *
   * @throws Exception
   */
  public function __construct(RouterInterface $router, string $moduleKey, string $adminClass = null)
  {
    $this->router = $router;
    $this->moduleKey = $moduleKey;
    if($adminClass)
    {
      $this->admin = new $adminClass($this);
    }
  }

  /**
   * @param bool $enabled
   *
   * @return $this
   */
  public function setEntityTranslateEnabled(bool $enabled = false): Module
  {
    if(AustralTools::usedImplements($this->entityManager->getClass(), EntityTranslateMasterInterface::class))
    {
      $this->entityTranslateEnabled = $enabled;
    }
    return $this;
  }

  /**
   * @return bool
   */
  public function getEntityTranslateEnabled(): bool
  {
    return $this->entityTranslateEnabled;
  }

  /**
   * Get parent
   * @return Module
   */
  public function getParent(): ?Module
  {
    return $this->parent;
  }

  /**
   * @param Module $parent
   *
   * @return Module
   */
  public function setParent(Module $parent): Module
  {
    $this->parent = $parent;
    $parent->addChildren($this);
    return $this;
  }

  /**
   * Get children
   * @return array
   */
  public function getChildren(): array
  {
    return $this->children;
  }

  /**
   * @param Module $module
   *
   * @return $this
   */
  public function addChildren(Module $module): Module
  {
    if(!array_key_exists($module->getModulePath(), $this->children))
    {
      $this->children[$module->getModulePath()] = $module;
    }
    return $this;
  }

  /**
   * @param Module $module
   *
   * @return $this
   */
  public function removeChildren(Module $module): Module
  {
    if(array_key_exists($module->getModulePath(), $this->children))
    {
      unset($this->children[$module->getModulePath()]);
    }
    return $this;
  }

  /**
   * @param array $children
   *
   * @return Module
   */
  public function setChildren(array $children): Module
  {
    $this->children = $children;
    return $this;
  }

  /**
   * Set navigation
   *
   * @param array $navigation
   *
   * @return Module
   */
  public function setNavigation(array $navigation): Module
  {
    $this->navigation = $navigation;
    return $this;
  }

  /**
   * @return boolean
   */
  public function navigationEnabled(): bool
  {
    return $this->navigation["enabled"];
  }

  /**
   * @return int|bool
   */
  public function navigationPosition()
  {
    return $this->navigation["position"];
  }

  /**
   * Get name
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * Set name
   *
   * @param string $name
   *
   * @return Module
   */
  public function setName(string $name): Module
  {
    $this->name = $name;
    return $this;
  }

  /**
   * Get picto
   * @return string
   */
  public function getPicto(): string
  {
    return $this->picto;
  }

  /**
   * Set picto
   *
   * @param string $picto
   *
   * @return Module
   */
  public function setPicto(string $picto): Module
  {
    $this->picto = $picto;
    return $this;
  }

  /**
   * Get pictoTile
   * @return string|null
   */
  public function getPictoTile(): ?string
  {
    return $this->pictoTile;
  }

  /**
   * Set pictoTile
   *
   * @param string|null $pictoTile
   *
   * @return Module
   */
  public function setPictoTile(?string $pictoTile): Module
  {
    $this->pictoTile = $pictoTile;
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
   * @return $this
   */
  public function setLanguageDefault(?string $languageDefault): Module
  {
    $this->languageDefault = $languageDefault;
    return $this;
  }

  /**
   * @return array
   */
  public function getLanguages(): array
  {
    return $this->languages;
  }

  /**
   * @param array $languages
   *
   * @return $this
   */
  public function setLanguages(array $languages): Module
  {
    $this->languages = $languages;
    return $this;
  }

  /**
   * Get modulePath
   * @return string
   */
  public function getModulePath(): string
  {
    return $this->modulePath;
  }

  /**
   * Set modulePath
   *
   * @param string $modulePath
   *
   * @return Module
   */
  public function setModulePath(string $modulePath): Module
  {
    $this->modulePath = $modulePath;
    return $this;
  }

  /**
   * @param EntityManagerInterface|DoctrineEntityManagerInterface|null $entityManager
   *
   * @return $this
   */
  public function setEntityManager($entityManager = null): Module
  {
    $this->entityManager = $entityManager;
    return $this;
  }

  /**
   * @return EntityManagerInterface|DoctrineEntityManagerInterface|null
   */
  public function getEntityManager()
  {
    return $this->entityManager;
  }

  /**
   * @return string
   */
  public function getDataHydrateClass(): string
  {
    return $this->dataHydrateClass;
  }

  /**
   * @param string $dataHydrateClass
   *
   * @return $this
   */
  public function setDataHydrateClass(string $dataHydrateClass): Module
  {
    $this->dataHydrateClass = $dataHydrateClass;
    return $this;
  }

  /**
   * @return AdminInterface|AdminModuleInterface
   */
  public function getAdmin()
  {
    return $this->admin;
  }

  /**
   * Get actionName
   * @return string
   */
  public function getActionName(): ?string
  {
    return $this->isEntityModule() ? null : $this->actionName;
  }

  /**
   * Set actionName
   *
   * @param string|null $actionName
   *
   * @return $this
   */
  public function setActionName(?string $actionName = null): Module
  {
    $this->actionName = $actionName;
    return $this;
  }

  /**
   * @return bool
   */
  public function isEntityModule(): bool
  {
    return $this->actionName === "entity";
  }

  /**
   * @param array $translates
   *
   * @return $this
   */
  public function setTranslates(array $translates): Module
  {
    $this->translates = $translates;
    return $this;
  }

  /**
   * @param string $key
   * @param string $value
   *
   * @return $this
   * @throws Exception
   */
  public function setTranslateByKey(string $key, string $value): Module
  {
    if(!array_key_exists($key, $this->translates))
    {
      throw new Exception("{$key} is not defined to translate ".implode(",", array_keys($this->translates)));
    }
    $this->translates[$key] = $value;
    return $this;
  }

  /**
   * @return string
   */
  public function translateSingular(): string
  {
    return $this->translates['singular'];
  }

  /**
   * @return string
   */
  public function translatePlural(): string
  {
    return $this->translates['plural'];
  }

  /**
   * @return string
   */
  public function translateKey(): string
  {
    return $this->translates['key'];
  }

  /**
   * @return string
   */
  public function translateGenre(): string
  {
    return $this->translates['type'];
  }

  /**
   * Get moduleKey
   * @return string
   */
  public function getModuleKey(): string
  {
    return $this->moduleKey;
  }

  /**
   * Set moduleKey
   *
   * @param string $moduleKey
   *
   * @return Module
   */
  public function setModuleKey(string $moduleKey): Module
  {
    $this->moduleKey = $moduleKey;
    return $this;
  }

  /**
   * Get actions
   * @return array
   */
  public function getPathActions(): array
  {
    return $this->pathActions;
  }

  /**
   * Set actions
   *
   * @param array $pathActions
   *
   * @return Module
   */
  public function setPathActions(array $pathActions): Module
  {
    $this->pathActions = $pathActions;
    return $this;
  }

  /**
   * Set actions
   *
   * @param string $actionKey
   *
   * @return bool
   */
  public function hasPathActions(string $actionKey): bool
  {
    return array_key_exists($actionKey, $this->pathActions);
  }

  /**
   * @param array $extendActions
   *
   * @return $this
   */
  public function setExtendActions(array $extendActions): Module
  {
    $this->extendActions = $extendActions;
    return $this;
  }

  /**
   * @param string $actionKey
   *
   * @return string|null
   */
  public function getMethodByActionKey(string $actionKey): ?string
  {
    return AustralTools::getValueByKey($this->extendActions, $actionKey);
  }

  /**
   * @param string|null $actionKey
   * @param array $parameters
   * @param int $referenceType
   *
   * @return string|null
   */
  public function generateUrl(string $actionKey = null, array $parameters = array(), int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
  {
    if(strpos($this->modulePath, "#") === 0)
    {
      $url = $this->router->generate(str_replace("#", '', $this->modulePath));
    }
    elseif($this->modulePath === "austral_admin_index")
    {
      $url = $this->router->generate("austral_admin_index");
    }
    else
    {
      if($actionKey && ($routes = AustralTools::getValueByKey($this->pathActions, $actionKey, array())))
      {
        $parameters["modulePath"] = $this->modulePath;
        $route = ($this->entityTranslateEnabled && array_key_exists("language", $routes)) ? $routes['language'] : $routes["default"];
        $routeCollection = $this->router->getRouteCollection()->get($route);
        $parametersRequired = $routeCollection->getRequirements();

        if(array_key_exists("actionKey", $parametersRequired))
        {
          $parameters["actionKey"] = $actionKey;
        }

        if(array_key_exists("language", $routes))
        {
          if(!array_key_exists("language", $parameters))
          {
            $parameters["language"] = $this->languageDefault;
          }
        }

        foreach(array_keys($parameters) as $key)
        {
          if(!array_key_exists($key, $parametersRequired))
          {
            unset($parameters[$key]);
          }
        }
        foreach(array_keys($parametersRequired) as $key)
        {
          if(!array_key_exists($key, $parameters))
          {
            $parameters[$key] = "__{$key}__";
          }
        }
        $url = $this->router->generate($route, $parameters, $referenceType);
      }
      else
      {
        $url = $this->router->generate("austral_admin_module_index", array("modulePath"=> $this->modulePath), $referenceType);
      }
    }
    return $url;
  }

  /**
   * @param array $grantedByActionKeys
   *
   * @return Module
   */
  public function setGrantedByActionKey(array $grantedByActionKeys): Module
  {
    $this->grantedByActionKeys = $grantedByActionKeys;
    return $this;
  }

  /**
   * @param ?string $actionKey
   *
   * @return bool
   */
  public function isGranted(?string $actionKey = null): bool
  {
    $actionKey = $actionKey ? : "default";
    return AustralTools::getValueByKey($this->grantedByActionKeys, strtolower($actionKey), false);
  }

  /**
   * @return bool
   */
  public function getIsSortable(): bool
  {
    return $this->isSortable && ($this->isGranted("change") || $this->isGranted("edit"));
  }

  /**
   * @param bool $isSortable
   *
   * @return Module
   */
  public function setIsSortable(bool $isSortable): Module
  {
    $this->isSortable = $isSortable;
    return $this;
  }

  /**
   * Get truncateEnabled
   * @return bool
   */
  public function getTruncateEnabled(): bool
  {
    return $this->truncateEnabled;
  }

  /**
   * @param bool $truncateEnabled
   *
   * @return Module
   */
  public function setTruncateEnabled(bool $truncateEnabled): Module
  {
    $this->truncateEnabled = $truncateEnabled;
    return $this;
  }

  /**
   * @return array
   */
  public function getDownloadFormats(): array
  {
    return $this->downloadFormats;
  }

  /**
   * @param array $downloadFormats
   *
   * @return Module
   */
  public function setDownloadFormats(array $downloadFormats): Module
  {
    $this->downloadFormats = $downloadFormats;
    return $this;
  }

  /**
   * @param string $format
   *
   * @return bool
   */
  public function downloadFormatIsDefined(string $format): bool
  {
    return in_array($format, $this->downloadFormats);
  }

  /**
   * @return bool
   */
  public function downloadEnabled(): bool
  {
    return count($this->downloadFormats) > 0;
  }

  /**
   * @return bool
   */
  public function getIsViewParentPage(): bool
  {
    return $this->isViewParentPage;
  }

  /**
   * @param bool $isViewParentPage
   *
   * @return $this
   */
  public function setIsViewParentPage(bool $isViewParentPage): Module
  {
    $this->isViewParentPage = $isViewParentPage;
    return $this;
  }

  /**
   * @return array
   */
  public function getParameters(): array
  {
    return $this->parameters;
  }

  /**
   * @param array $parameters
   *
   * @return Module
   */
  public function setParameters(array $parameters): Module
  {
    $this->parameters = $parameters;
    return $this;
  }

  /**
   * @param string $key
   * @param mixed $parameters
   *
   * @return Module
   */
  public function addParameters(string $key, $parameters): Module
  {
    $this->parameters[$key] = $parameters;
    return $this;
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return mixed
   */
  public function getParametersByKey(string $key, $default = null)
  {
    return array_key_exists($key, $this->getParameters()) ? $this->parameters[$key] : $default;
  }

}