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

use Austral\AdminBundle\Admin\Event\ActionAdminEvent;
use Austral\AdminBundle\Admin\Event\ChangeValueAdminEvent;
use Austral\AdminBundle\Admin\Event\AdminEventInterface;
use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;
use Austral\AdminBundle\Admin\Event\SortableAdminEvent;
use Austral\AdminBundle\Module\Module;
use Austral\Contracts\Entity\EntityInterface;
use Austral\Contracts\Translate\TranslateMasterInterface;
use Austral\HttpBundle\Entity\Domain;
use Austral\ListBundle\Column\Action;
use Austral\ListBundle\Column\Actions;
use Austral\ListBundle\Column\Interfaces\ColumnActionInterface;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\ToolsBundle\AustralTools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Admin  Abstract.
 * @template T of object
 *
 * @implements AdminInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
abstract class Admin implements AdminInterface
{

  const array DEFAULT_LISTENER = array(
    AdminEventInterface::ADMIN_EVENT_LIST_START             => "adminEventListStart",
    AdminEventInterface::ADMIN_EVENT_LIST_HYDRATE           => "adminEventListHydrate",
    AdminEventInterface::ADMIN_EVENT_LIST_END               => "adminEventListEnd",

    AdminEventInterface::ADMIN_EVENT_FORM_START             => "adminEventFormStart",
    AdminEventInterface::ADMIN_EVENT_FORM_END               => "adminEventFormEnd",

    AdminEventInterface::ADMIN_EVENT_ACTION_START           => "adminEventActionStart",
    AdminEventInterface::ADMIN_EVENT_ACTION_END             => "adminEventActionEnd",

    AdminEventInterface::ADMIN_EVENT_CHANGE_VALUE_START      => "adminEventChangeValueStart",

    AdminEventInterface::ADMIN_EVENT_SORTABLE_END           => "adminEventSortableEnd",
  );

  /**
   * @var ContainerInterface|null
   */
  protected ?ContainerInterface $container = null;

  /**
   * @var TranslatorInterface|null
   */
  protected ?TranslatorInterface $translator = null;

  /**
   * @var array<string, array<int, list<string>>>
   */
  protected array $events = array();

  /**
   * @var EntityInterface<T>|null
   */
  protected ?EntityInterface $object = null;

  /**
   * @var Module<T>|null
   */
  protected ?Module $module = null;

  /**
   * @var bool
   */
  protected bool $formIsValidate = true;

  /**
   * @var string|null
   */
  protected ?string $currentLanguage = null;

  /**
   * Admin constructor.
   *
   * @final
   */
  public function __construct()
  {

    /**
     * @var string $eventName
     * @var string|array{0: string, 1?: int}|list<array{0: string, 1?: int}> $params
     */
    foreach(self::DEFAULT_LISTENER as $eventName => $params)
    {
      $this->addEvents($eventName, $params);
    }

    /**
     * @var string $eventName
     * @var string|array{0: string, 1: int}|list<array{0: string, 1?: int}> $params
     */
    foreach($this->getEvents() as $eventName => $params)
    {
      $this->addEvents($eventName, $params);
    }
  }

  /**
   * @param string $eventName
   * @param string|array{0: string, 1: int}|list<array{0: string, 1?: int}> $params
   */
  private function addEvents(string $eventName, mixed $params): void
  {
    if (\is_string($params)) {
      $this->addEvent($eventName, $params);
    } elseif (isset($params[0]) && \is_array($params[0])) {
      /** @var array{0: string, 1?: int} $param */
      foreach ($params as $param) {
        $this->addEvent($eventName, $param[0], (int) ($param[1] ?? 0));
      }
    } else {
      $this->addEvent($eventName, is_string($params[0]) ? $params[0] : "", (int) ($params[1] ?? 0));
    }
  }

  /**
   * @param string $eventName
   * @param string $methodName
   * @param int $priority
   */
  private function addEvent(string $eventName, string $methodName, int $priority = 0): void
  {
    $this->events[$eventName][$priority][] = $methodName;
    ksort($this->events[$eventName], SORT_NUMERIC);
  }

  /**
   * @param Module<T>|null $module
   * @return $this
   */
  public function setModule(?Module $module = null): self
  {
    $this->module = $module;
    return $this;
  }

  /**
   * @param string $currentLanguage
   *
   * @return $this
   */
  public function setCurrentLanguage(string $currentLanguage): self
  {
    $this->currentLanguage = $currentLanguage;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setContainer($container): self
  {
    $this->container = $container;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setTranslator($translator): Admin
  {
    $this->translator = $translator;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getEvents(): array
  {
    return $this->events;
  }

  /**
   * @inheritDoc
   */
  public function dispatch(string $eventName, AdminEventInterface $managerEvent): void
  {
    foreach(AustralTools::valueByKey($this->events, $eventName, array()) as $methods)
    {
      foreach($methods as $method)
      {
        if(method_exists($this, $method))
        {
          $this->$method($managerEvent);
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function isDevEnv(): bool
  {
    return $this->container?->getParameter("kernel.environment") === "dev";
  }

  /**
   * @inheritDoc
   */
  public function setObject(EntityInterface $object): self
  {
    $this->object = $object;
    return $this;
  }

  /**
   * @return bool
   * @final
   */
  public function formIsValidate(): bool
  {
    return $this->formIsValidate;
  }

  /**
   * @var array<int, Module<T>>
   */
  protected array $childrenModules = array();

  /**
   * @param ListAdminEvent<T> $listAdminEvent
   */
  public function listChildrenModules(ListAdminEvent $listAdminEvent): void
  {
    if($this->module instanceof Module)
    {
      /** @var Module<T> $module */
      foreach ($this->module->getChildren() as $module)
      {
        if($module->isGranted())
        {
          $this->childrenModules[] = $module;
        }
      }
    }
    $listAdminEvent->getTemplateParameters()->addParameters("childrenModules", $this->childrenModules);
  }

  /**
   * @param ListAdminEvent<T> $listAdminEvent
   *
   * @final
   */
  protected function adminEventListStart(ListAdminEvent $listAdminEvent): void
  {
    if($listAdminEvent->getListMapper())
    {
      $listAdminEvent->getTemplateParameters()->setPath("@AustralAdmin/List/index.html.twig");
      $listAdminEvent->getTemplateParameters()->addParameters("pageType", "module");
    }
    else
    {
      $listAdminEvent->getTemplateParameters()->setPath("@AustralAdmin/Module/index.html.twig");
      $listAdminEvent->getTemplateParameters()->addParameters("pageType", "action");
    }
  }

  /**
   * @param ListAdminEvent<T> $listAdminEvent
   *
   * @throws \Exception
   * @final
   */
  protected function adminEventListHydrate(ListAdminEvent $listAdminEvent): void
  {
    $listAdminEvent->getListMapper()?->generate();
  }

  /**
   * @param ListAdminEvent<T> $listAdminEvent
   *
   * @final
   */
  protected function adminEventListEnd(ListAdminEvent $listAdminEvent): void
  {
    /** @var list<Action<T>> $actionsColumn */
    $actionsColumn = [];

    if(($listMapper = $listAdminEvent->getListMapper()) && $this->module)
    {
      if($this->module->isGranted("edit"))
      {
        $actionsColumn[] = new Action("edit", "actions.edit",
          $this->module->generateUrl("edit"),
          "austral-picto-edit",
          array(
            "attr"  =>  array(
              "data-click-actions"        =>  "reload",
              "data-reload-elements-key"  =>  "container"
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        );
      }

      if($this->module->isGranted("duplicate"))
      {
        $actionsColumn[] = new Action("duplicate", "actions.duplicate",
          $this->module->generateUrl("duplicate"),
        "austral-picto-stack",
          array(
            "attr"  =>  array(
              "data-click-actions"        =>  "reload",
              "data-reload-elements-key"  =>  "container"
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        );
        $domainsWithoutVirtual = $listAdminEvent->getAdminHandler()->getDomainsManagement()?->getDomainsWithoutVirtual();
        if($this->module->getEnableMultiDomain() && $domainsWithoutVirtual && (count($domainsWithoutVirtual) > 1))
        {
          /** @var Domain<T>[] $domainsForDuplicate */
          $domainsForDuplicate = [];
          /** @var Domain<T> $domain */
          foreach($domainsWithoutVirtual as $domain)
          {
            if($domain->getId() !== $this->module->getFilterDomainId() && $domain->getIsMaster())
            {
              $domainsForDuplicate[] = $domain;
            }
          }
          if(count($domainsForDuplicate) > 1)
          {
            foreach($domainsForDuplicate as $domain)
            {
              $parametersDuplicateUrl = array('domainId' => $domain->getId());
              if($domain->getLanguage())
              {
                $parametersDuplicateUrl["language"] = $domain->getLanguage();
              }
              $actionsColumn[] = new Action("duplicate", "actions.duplicate_by_domain",
                $this->module->generateUrl("duplicate", $parametersDuplicateUrl),
                "austral-picto-stack",
                array(
                  "attr"  =>  array(
                    "data-click-actions"        =>  "reload",
                    "data-reload-elements-key"  =>  "container"
                  ),
                  "translateParameters" => array(
                    "module_name"     =>  $this->module->translateSingular(),
                    "module_gender"   =>  $this->module->translateGenre(),
                    "%domainName%"    =>  $domain->getName()
                  )
                )
              );
            }
          }



        }
      }

      if($this->module->isGranted("delete"))
      {
        $actionsColumn[] = new Action("delete", "actions.delete",
          $this->module->generateUrl("delete"),
          "austral-picto-trash",
          array(
            "data-url" =>  true,
            "attr"    =>  array(
              "data-click-actions"        =>  "remove, confirm",
              "data-alert"                =>  "confirm",
              "data-reload-elements-key"  =>  "container",
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        );
      }

      if($this->module->isGranted("download") && $this->module->downloadEnabled())
      {
        $formats = $this->module->getDownloadFormats();
        if(count($formats) > 1)
        {
          /** @var list<ColumnActionInterface<T>> $actionsDownload */
          $actionsDownload = [];
          foreach($this->module->getDownloadFormats() as $format)
          {
            $actionsDownload[] = new Action("download-{$format}", "download.format.{$format}",
              $this->module->generateUrl("download", array("format" => $format)),
              "austral-picto-cloud-download",
              array(
                "translateParameters" => array(
                  "module_name"     =>  $this->module->translateSingular(),
                  "module_gender"   =>  $this->module->translateGenre()
                )
              )
            );
          }

          /** @var Actions<T> $actionDownload */
          $actionDownload = new Actions("download", "actions.download.default",
            null,
            "austral-picto-cloud-download",
            array(
              "translateParameters" => array(
                "module_name"     =>  $this->module->translateSingular(),
                "module_gender"   =>  $this->module->translateGenre()
              )
            )
          );
          $actionDownload->addActions($actionsDownload);
        }
        else
        {
          /** @var Action<T> $actionDownload */
          $actionDownload = new Action("download", "actions.download.".AustralTools::first($formats),
            $this->module->generateUrl("download", array("format"=>AustralTools::first($formats))),
            "austral-picto-cloud-download",
            array(
              "class"   =>  "button-picto",
              "translateParameters" => array(
                "module_name"     =>  $this->module->translateSingular(),
                "module_gender"   =>  $this->module->translateGenre()
              )
            )
          );
        }
        $listMapper->addAction($actionDownload, 10);
      }

      if($this->module->isGranted("truncate") && $this->module->getTruncateEnabled())
      {
        /** @var Action<T> $actionTruncate */
        $actionTruncate = new Action("truncate", "actions.truncate",
          $this->module->generateUrl("truncate"),
          "austral-picto-trash",
          array(
            "attr"    =>  array(
              "data-click-actions"        =>  "reload, confirm",
              "data-reload-elements-key"  =>  "container",
              "data-alert"                =>  "confirm",
              "data-alert-options"        =>  json_encode(array(
                'button'  =>  array(
                  "confirm" => $this->translator?->trans("sweetAlert.confirm.actions.confirmAll", array(), $listMapper->getTranslateDomain()),
                  "cancel"  => $this->translator?->trans("sweetAlert.confirm.actions.cancel", array(), $listMapper->getTranslateDomain()),
                )
              ))
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        );
        $listMapper->addAction($actionTruncate, 30);
      }

      if($this->module->isGranted("create"))
      {
        /** @var Action<T> $actionCreate */
        $actionCreate = new Action("create", "actions.create",
          $this->module->generateUrl("create"),
          null,
          array(
            "keybord-shortcut"            =>  "CTRL+MAJ+N",
            "attr"    =>  array(
              "data-click-actions"    =>  "reload",
              "data-reload-elements-key"  =>  "container"
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        );
        $listMapper->addAction($actionCreate, 40);
      }

      if($this->module->isGranted("edit"))
      {
        $actionsColumn[] = new Action("edit_blank", "actions.newOnglet",
          $this->module->generateUrl("edit"),
          "austral-picto-corner-forward",
          array(
            "attr"  =>  array(
              "itemClass"   =>  "in-to-context first",
              "target"      =>  "_blank",
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        );
      }

      $entityClass = $this->module->getEntityManager()?->getClass();
      if($entityClass && AustralTools::usedImplements($entityClass, "Austral\EntityBundle\Entity\Interfaces\SeoInterface"))
      {
        try {
          $actionsColumn[] = new Action("goTo", "actions.goTo",
            $listAdminEvent->getAdminHandler()->generateUrl("austral_website_page", array('slug'=>"__refUrl__")),
            "austral-picto-corner-forward",
            array(
              "attr"  =>  array(
                "itemClass"   =>  "in-to-context",
                "target"      =>  "_blank"
              ),
              "translateParameters" => array(
                "module_name"     =>  $this->module->translateSingular(),
                "module_gender"   =>  $this->module->translateGenre()
              )
            )
          );
        } catch(\Exception $e) {

        }
      }

      /** @var ColumnActionInterface<T> $action */
      foreach ($actionsColumn as $action)
      {
        $listMapper->addAction($action);
      }

      $listAdminEvent->getTemplateParameters()->addParameters("list", array(
        "mapper"    =>  $listMapper,
      ));
      $listAdminEvent->getTemplateParameters()->addParameters("filter", array(
        "mapper"    =>  $listAdminEvent->getFilterMapper(),
      ));
    }

  }

  /**
   * @param FormAdminEvent<T> $formAdminEvent
   *
   * @final
   */
  protected function adminEventFormStart(FormAdminEvent $formAdminEvent): void
  {
    $formAdminEvent->getTemplateParameters()->setPath("@AustralAdmin/Form/index.html.twig");
    $formAdminEvent->getTemplateParameters()->addParameters("pageType", "action");
  }

  /**
   * @param FormAdminEvent<T> $formAdminEvent
   *
   * @final
   */
  protected function adminEventFormEnd(FormAdminEvent $formAdminEvent): void
  {
    $formAdminEvent->getTemplateParameters()->addParameters("form", array(
      "mapper"    =>  $formAdminEvent->getFormMapper(),
      "view"      =>  $formAdminEvent->getForm()->createView()
    ));
    if($this->module)
    {
      $formAdminEvent->getTemplateParameters()
        ->breadcrumb()
        ->addBreadcrumbEntry(clone $this->module,"pages.form.{$formAdminEvent->getFormMapper()->getFormTypeAction()}.name");


      if($this->module->isGranted("duplicate"))
      {
        $formAdminEvent->getFormMapper()->addAction(new Action("duplicate", "actions.duplicate",
          $this->module->generateUrl("duplicate", array('id'=>$formAdminEvent->getFormMapper()->getObject()->getId())),
          "austral-picto-stack",
          array(
            "keybord-shortcut"            =>  "CTRL+MAJ+D",
            "attr"    =>  array(
              "data-click-actions"        =>  "reload",
              "data-reload-elements-key"  =>  "container",
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        ), 40
        );
      }
      if($this->module->isGranted("delete"))
      {
        $formAdminEvent->getFormMapper()->addAction(new Action("delete", "actions.delete",
          $this->module->generateUrl("delete", array('id'=>$formAdminEvent->getFormMapper()->getObject()->getId())),
          "austral-picto-trash",
          array(
            "keybord-shortcut"            =>  "CTRL+MAJ+DELETE",
            "attr"    =>  array(
              "data-click-actions"        =>  "reload, confirm",
              "data-reload-elements-key"  =>  "container",
              "data-alert"                =>  "confirm",
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        ), 80
        );
      }
      if($this->module->isGranted("create"))
      {
        $formAdminEvent->getFormMapper()->addAction(new Action("create", "actions.create",
          $this->module->generateUrl("create"),
          "austral-picto-square-plus",
          array(
            "keybord-shortcut"            =>  "CTRL+MAJ+N",
            "attr"    =>  array(
              "data-click-actions"        =>  "reload",
              "data-reload-elements-key"  =>  "container"
            ),
            "translateParameters" => array(
              "module_name"     =>  $this->module->translateSingular(),
              "module_gender"   =>  $this->module->translateGenre()
            )
          )
        ), 90
        );
      }
    }
  }

  /**
   * @param ActionAdminEvent<T> $actionAdminEvent
   *
   * @final
   */
  protected function adminEventActionStart(ActionAdminEvent $actionAdminEvent): void
  {
    $actionAdminEvent->getTemplateParameters()->setPath("@AustralAdmin/Action/index.html.twig");
    $actionAdminEvent->getTemplateParameters()->addParameters("pageType", "action");
  }

  /**
   * @param ActionAdminEvent<T> $actionAdminEvent
   *
   * @final
   */
  protected function adminEventActionEnd(ActionAdminEvent $actionAdminEvent): void
  {
    $actionAdminEvent->getTemplateParameters()->addParameters("object", $actionAdminEvent->getObject());
    if($this->module)
    {
      $actionAdminEvent->getTemplateParameters()
        ->breadcrumb()
        ->addBreadcrumbEntry(clone $this->module,"pages.action.{$actionAdminEvent->getActionKey()}.name");
    }
  }

  /**
   * @param ChangeValueAdminEvent<T> $changeValueAdminEvent
   *
   * @final
   */
  protected function adminEventChangeValueStart(ChangeValueAdminEvent $changeValueAdminEvent): void
  {
    $object = $changeValueAdminEvent->getObject();

    if($changeValueAdminEvent->getFieldname() === "urlParameter.status")
    {
      if($urlParameterId = $changeValueAdminEvent->getRequest()->get('urlParameterId'))
      {
        if($urlParameter = $this->container->get('austral.seo.url_parameter.management')->getUrlParametersById($urlParameterId))
        {
          $urlParameter->setStatus($changeValueAdminEvent->getValue());
        }
      }
      else
      {
        $urlParameters = $this->container->get('austral.seo.url_parameter.management')->getUrlParametersByObject($object);
        /** @var UrlParameterInterface $urlParameter */
        foreach ($urlParameters as $urlParameter)
        {
          $urlParameter->setStatus($changeValueAdminEvent->getValue());
        }
      }
    }
    else
    {
      $setter = AustralTools::createSetterFunction($changeValueAdminEvent->getFieldname());
      if(method_exists($object, $setter))
      {
        $object->$setter($changeValueAdminEvent->getValue());
      }
      elseif($object instanceof TranslateMasterInterface)
      {
        if(method_exists($object->getTranslateCurrent(), $setter))
        {
          $object->getTranslateCurrent()->$setter($changeValueAdminEvent->getValue());
        }
      }
    }
  }

  /**
   * @param SortableAdminEvent<T> $sortableAdminEvent
   *
   * @final
   */
  protected function adminEventSortableEnd(SortableAdminEvent $sortableAdminEvent): void
  {
    $setter = AustralTools::createSetterFunction($sortableAdminEvent->getFieldname());
    $objects = $sortableAdminEvent->getEntityManager()->selectAll("id", "ASC", $sortableAdminEvent->getQueryBuilder());

    foreach($sortableAdminEvent->getPositions() as $position => $value)
    {
      /** @var EntityInterface<T>|null $object */
      $object = AustralTools::valueByKey($objects, $value);
      if($object)
      {
        $this->changePositionByValue($object, $setter, $position);
        $sortableAdminEvent->getEntityManager()->update($object, false);
      }
    }
    $sortableAdminEvent->getEntityManager()->flush();
  }

  /**
   * @param EntityInterface<T> $object
   * @param string $setter
   * @param int $position
   *
   * @return void
   */
  protected function changePositionByValue(EntityInterface $object, string $setter, int $position)
  {
    if(method_exists($object, $setter))
    {
      $object->$setter($position+1);
    }
    elseif($object instanceof TranslateMasterInterface)
    {
      if(method_exists($object->getTranslateCurrent(), $setter))
      {
        $object->getTranslateCurrent()->$setter($position+1);
      }
    }
  }

}