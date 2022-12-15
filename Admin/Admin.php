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
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;
use Austral\HttpBundle\Entity\Domain;
use Austral\ListBundle\Column\Action;
use Austral\ListBundle\Column\Actions;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\ToolsBundle\AustralTools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Admin  Abstract.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
abstract class Admin implements AdminInterface
{

  const DEFAULT_LISTENER = array(
    ListAdminEvent::EVENT_START             => "adminEventListStart",
    ListAdminEvent::EVENT_HYDRATE           => "adminEventListHydrate",
    ListAdminEvent::EVENT_END               => "adminEventListEnd",

    FormAdminEvent::EVENT_START             => "adminEventFormStart",
    FormAdminEvent::EVENT_END               => "adminEventFormEnd",

    ActionAdminEvent::EVENT_START           => "adminEventActionStart",
    ActionAdminEvent::EVENT_END             => "adminEventActionEnd",

    ChangeValueAdminEvent::EVENT_START      => "adminEventChangeValueStart",

    SortableAdminEvent::EVENT_END           => "adminEventSortableEnd",
  );

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var TranslatorInterface
   */
  protected TranslatorInterface $translator;

  /**
   * @var array
   */
  protected array $events = array();

  /**
   * @var EntityInterface
   */
  protected EntityInterface $object;

  /**
   * @var Module
   */
  protected Module $module;

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
   * @param Module $module
   *
   * @final
   */
  public function __construct(Module $module)
  {
    $this->module = $module;
    /**
     * @var string $eventName
     * @var string|array $params
     */
    foreach(self::DEFAULT_LISTENER as $eventName => $params)
    {
      if (\is_string($params)) {
        $this->addEvent($eventName, $params, 0);
      } elseif (\is_string($params[0])) {
        $this->addEvent($eventName, $params[0], $params[1] ?? 0);
      } else {
        foreach ($params as $param) {
          $this->addEvent($eventName, $param[0], $param[1] ?? 0);
        }
      }
    }

    /**
     * @var string $eventName
     * @var string|array $params
     */
    foreach($this->getEvents() as $eventName => $params)
    {
      if (\is_string($params)) {
        $this->addEvent($eventName, $params, -1024);
      } elseif (\is_string($params[0])) {
        $this->addEvent($eventName, $params[0], $params[1] ?? -1024);
      } else {
        foreach ($params as $param) {
          $this->addEvent($eventName, $param[0], $param[1] ?? -1024);
        }
      }
    }
  }

  /**
   * @param $eventName
   * @param $methodName
   * @param int $priority
   */
  private function addEvent($eventName, $methodName, int $priority = 0)
  {
    $this->events[$eventName][$priority][] = $methodName;
    ksort($this->events[$eventName], SORT_NUMERIC);
  }

  /**
   * @param string $currentLanguage
   *
   * @return $this
   */
  public function setCurrentLanguage(string $currentLanguage): Admin
  {
    $this->currentLanguage = $currentLanguage;
    return $this;
  }

  /**
   * @param $container
   *
   * @return $this
   * @final
   */
  public function setContainer($container): Admin
  {
    $this->container = $container;
    return $this;
  }

  /**
   * @param $translator
   *
   * @return $this
   * @final
   */
  public function setTranslator($translator): Admin
  {
    $this->translator = $translator;
    return $this;
  }

  /**
   * @return array
   */
  public function getEvents(): array
  {
    return array();
  }

  /**
   * @param string $eventName
   * @param AdminEventInterface $managerEvent
   *
   * @final
   */
  public function dispatch(string $eventName, AdminEventInterface $managerEvent)
  {
    foreach(AustralTools::getValueByKey($this->events, $eventName, array()) as $methods)
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
   * @return bool
   */
  public function isDevEnv(): bool
  {
    return $this->container->getParameter("kernel.environment") === "dev";
  }

  /**
   * @param EntityInterface $object
   *
   * @return $this
   * @final
   */
  public function setObject(EntityInterface $object): Admin
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
   * @var array
   */
  protected array $childrenModules = array();

  /**
   * @param ListAdminEvent $listAdminEvent
   */
  public function listChildrenModules(ListAdminEvent $listAdminEvent)
  {
    /** @var Module $module */
    foreach ($this->module->getChildren() as $module)
    {
      if($module->isGranted())
      {
        $this->childrenModules[] = $module;
      }
    }
    $listAdminEvent->getTemplateParameters()->addParameters("childrenModules", $this->childrenModules);
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   *
   * @final
   */
  protected function adminEventListStart(ListAdminEvent $listAdminEvent)
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
   * @param ListAdminEvent $listAdminEvent
   *
   * @throws \Exception
   * @final
   */
  protected function adminEventListHydrate(ListAdminEvent $listAdminEvent)
  {
    $listAdminEvent->getListMapper()->generate();
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   *
   * @final
   */
  protected function adminEventListEnd(ListAdminEvent $listAdminEvent)
  {
    if($listMapper = $listAdminEvent->getListMapper())
    {
      if($this->module->isGranted("edit"))
      {
        $listMapper->addColumnAction(new Action("edit", "actions.edit",
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
          )
        );
      }

      if($this->module->isGranted("duplicate"))
      {
        $listMapper->addColumnAction(new Action("duplicate", "actions.duplicate",
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
          )
        );

        if($this->module->getEnableMultiDomain())
        {
          /** @var Domain $domain */
          foreach($listAdminEvent->getAdminHandler()->getDomainsManagement()->getDomainsWithoutVirtual() as $domain)
          {
            if($domain->getId() !== $this->module->getFilterDomainId())
            {
              $listMapper->addColumnAction(new Action("duplicate", "actions.duplicate_by_domain",
                  $this->module->generateUrl("duplicate", array('domainId' => $domain->getId())),
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
                )
              );
            }
          }
        }
      }

      if($this->module->isGranted("delete"))
      {
        $listMapper->addColumnAction(new Action("delete", "actions.delete",
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
          )
        );
      }

      if($this->module->isGranted("download") && $this->module->downloadEnabled())
      {

        $formats = $this->module->getDownloadFormats();
        if(count($formats) > 1)
        {
          $actions = new Actions("download", "actions.download.default",
            null,
            "austral-picto-cloud-download",
            array(
              "translateParameters" => array(
                "module_name"     =>  $this->module->translateSingular(),
                "module_gender"   =>  $this->module->translateGenre()
              )
            )
          );

          foreach($this->module->getDownloadFormats() as $format)
          {
            $actions->addAction(new Action("download-{$format}", "download.format.{$format}",
              $this->module->generateUrl("download", array("format" => $format)),
              "austral-picto-cloud-download",
              array(
                "translateParameters" => array(
                  "module_name"     =>  $this->module->translateSingular(),
                  "module_gender"   =>  $this->module->translateGenre()
                )
              )
            ));
          }
        }
        else
        {
          $actions = new Action("download", "actions.download.".AustralTools::first($formats),
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
        $listMapper->addAction($actions, 10);
      }

      if($this->module->isGranted("truncate") &&
        $this->module->getTruncateEnabled())
      {
        $listAdminEvent->getListMapper()->addAction(new Action("truncate", "actions.truncate",
            $this->module->generateUrl("truncate"),
            "austral-picto-trash",
            array(
              "attr"    =>  array(
                "data-click-actions"        =>  "reload, confirm",
                "data-reload-elements-key"  =>  "container",
                "data-alert"                =>  "confirm",
                "data-alert-options"        =>  json_encode(array(
                  'button'  =>  array(
                    "confirm" => $this->translator->trans("sweetAlert.confirm.actions.confirmAll", array(), $listMapper->getTranslateDomain()),
                    "cancel"  => $this->translator->trans("sweetAlert.confirm.actions.cancel", array(), $listMapper->getTranslateDomain()),
                  )
                ))
              ),
              "translateParameters" => array(
                "module_name"     =>  $this->module->translateSingular(),
                "module_gender"   =>  $this->module->translateGenre()
              )
            )
          ), 30
        );
      }

      if($this->module->isGranted("create"))
      {
        $listMapper->addAction(new Action("create", "actions.create",
            $this->module->generateUrl("create"),
            null,
            array(
              "attr"    =>  array(
                "data-click-actions"    =>  "reload",
                "data-reload-elements-key"  =>  "container"
              ),
              "translateParameters" => array(
                "module_name"     =>  $this->module->translateSingular(),
                "module_gender"   =>  $this->module->translateGenre()
              )
            )
          ), 40
        );
      }

      if($this->module->isGranted("edit"))
      {
        $listMapper->addColumnAction(new Action("edit_blank", "actions.newOnglet",
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
          )
        );
      }

      if(AustralTools::usedImplements($this->module->getEntityManager()->getClass(), "Austral\EntityBundle\Entity\Interfaces\SeoInterface"))
      {
        try {
          $listMapper->addColumnAction(new Action("goTo", "actions.goTo",
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
            )
          );
        } catch(\Exception $e) {

        }
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
   * @param FormAdminEvent $formAdminEvent
   *
   * @final
   */
  protected function adminEventFormStart(FormAdminEvent $formAdminEvent)
  {
    $formAdminEvent->getTemplateParameters()->setPath("@AustralAdmin/Form/index.html.twig");
    $formAdminEvent->getTemplateParameters()->addParameters("pageType", "action");
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @final
   */
  protected function adminEventFormEnd(FormAdminEvent $formAdminEvent)
  {
    $formAdminEvent->getTemplateParameters()->addParameters("form", array(
      "mapper"    =>  $formAdminEvent->getFormMapper(),
      "view"      =>  $formAdminEvent->getForm()->createView()
    ));
    $formAdminEvent->getTemplateParameters()
      ->breadcrumb()
      ->addBreadcrumbEntry(clone $this->module,"pages.form.{$formAdminEvent->getFormMapper()->getFormTypeAction()}.name");


    if($this->module->isGranted("duplicate"))
    {
      $formAdminEvent->getFormMapper()->addAction(new Action("duplicate", "actions.duplicate",
          $this->module->generateUrl("duplicate", array('id'=>$formAdminEvent->getFormMapper()->getObject()->getId())),
          "austral-picto-stack",
          array(
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

  /**
   * @param ActionAdminEvent $actionAdminEvent
   *
   * @final
   */
  protected function adminEventActionStart(ActionAdminEvent $actionAdminEvent)
  {
    $actionAdminEvent->getTemplateParameters()->setPath("@AustralAdmin/Action/index.html.twig");
    $actionAdminEvent->getTemplateParameters()->addParameters("pageType", "action");
  }

  /**
   * @param ActionAdminEvent $actionAdminEvent
   *
   * @final
   */
  protected function adminEventActionEnd(ActionAdminEvent $actionAdminEvent)
  {
    $actionAdminEvent->getTemplateParameters()->addParameters("object", $actionAdminEvent->getObject());
    $actionAdminEvent->getTemplateParameters()
      ->breadcrumb()
      ->addBreadcrumbEntry(clone $this->module,"pages.action.{$actionAdminEvent->getActionKey()}.name");
  }

  /**
   * @param ChangeValueAdminEvent $changeValueAdminEvent
   *
   * @final
   */
  protected function adminEventChangeValueStart(ChangeValueAdminEvent $changeValueAdminEvent)
  {
    $object = $changeValueAdminEvent->getObject();

    if($changeValueAdminEvent->getFieldname() === "urlParameter.status")
    {
      $urlParameters = $this->container->get('austral.seo.url_parameter.management')->getUrlParametersByObject($object);
      /** @var UrlParameterInterface $urlParameter */
      foreach ($urlParameters as $urlParameter)
      {
        $urlParameter->setStatus($changeValueAdminEvent->getValue());
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
   * @param SortableAdminEvent $sortableAdminEvent
   *
   * @final
   */
  protected function adminEventSortableEnd(SortableAdminEvent $sortableAdminEvent)
  {
    $setter = AustralTools::createSetterFunction($sortableAdminEvent->getFieldname());
    $objects = $sortableAdminEvent->getEntityManager()->selectAll("id", "ASC", $sortableAdminEvent->getQueryBuilder());

    foreach($sortableAdminEvent->getPositions() as $poition => $value)
    {
      /** @var EntityInterface $object */
      if($object = AustralTools::getValueByKey($objects, $value, null))
      {
        $this->changePositionByValue($object, $setter, $poition);
        $sortableAdminEvent->getEntityManager()->update($object, false);
      }
    }
    $sortableAdminEvent->getEntityManager()->flush();
  }

  /**
   * @param EntityInterface $object
   * @param $setter
   * @param $position
   *
   * @return void
   */
  protected function changePositionByValue(EntityInterface $object, $setter, $position)
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