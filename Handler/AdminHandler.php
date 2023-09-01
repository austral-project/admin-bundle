<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\AdminBundle\Handler;

use Austral\AdminBundle\Admin\Event\ActionAdminEvent;
use Austral\AdminBundle\Admin\Event\ChangeValueAdminEvent;
use Austral\AdminBundle\Admin\Event\DownloadAdminEvent;
use Austral\AdminBundle\Admin\Event\FilterEventInterface;
use Austral\AdminBundle\Admin\Event\HttpCacheClearAdminEvent;
use Austral\AdminBundle\Admin\Event\SortableAdminEvent;
use Austral\AdminBundle\Admin\Event\TruncateAdminEvent;
use Austral\AdminBundle\Event\DashboardEvent;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Services\Download;
use Austral\AdminBundle\Handler\Interfaces\AdminHandlerInterface;
use Austral\AdminBundle\Handler\Base\BaseAdminHandler;
use Austral\AdminBundle\Admin\Event\DeleteAdminEvent;
use Austral\AdminBundle\Admin\Event\DuplicateAdminEvent;
use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;
use Austral\AdminBundle\Admin\AdminModuleInterface;
use Austral\CacheBundle\Event\HttpCacheEvent;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\FilterBundle\Filter\Filter;
use Austral\FilterBundle\Mapper\FilterMapper;
use Austral\FormBundle\Form\Type\FormTypeInterface;
use Austral\FormBundle\Mapper\FormMapper;
use Austral\FormBundle\Event\FormEvent;
use Austral\HttpBundle\Mapping\DomainFilterMapping;
use Austral\ListBundle\DataHydrate\DataHydrateInterface;
use Austral\ListBundle\Filter\FilterMapperInterface;
use Austral\ListBundle\Mapper\ListMapper;
use Austral\ListBundle\Section\Section;
use Austral\NotifyBundle\Notification\Push;
use Austral\ToolsBundle\AustralTools;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;

use \Exception;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handler Admin.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class AdminHandler extends BaseAdminHandler implements AdminHandlerInterface
{

  /**
   * @var string
   */
  protected string $debugContainer = "austral.admin.handler";

  /**
   * @return $this
   * @throws Exception
   */
  public function index(): AdminHandler
  {
    if($this->module->getModuleKey() == "austral_admin_dashboard")
    {
      return $this->dashboard();
    }
    return $this->list();
  }

  /**
   * @return $this
   */
  public function dashboard(): AdminHandler
  {
    $this->debug->stopWatchStart("austral.admin.handler.dashboard", $this->debugContainer);
    $listAdminEvent = new ListAdminEvent(
      $this,
      $this->templateParameters,
      "dashboard"
    );
    $this->module->getAdmin()->dispatch(ListAdminEvent::EVENT_DASHBOARD, $listAdminEvent);

    $dashboardEvent = new DashboardEvent();
    $this->dispatcher->dispatch($dashboardEvent, DashboardEvent::EVENT_AUSTRAL_ADMIN_DASHBOARD);

    $this->templateParameters->setPath("@AustralAdmin/Dashboard/index.html.twig");
    $this->templateParameters->addParameters("dashboard", $dashboardEvent->getDashboardBlock());
    $this->debug->stopWatchStop("austral.admin.handler.dashboard");
    return $this;
  }

  /**
   * @return $this
   * @throws \Exception
   */
  public function list(): AdminHandler
  {
    $this->debug->stopWatchStart("austral.admin.handler.list", $this->debugContainer);
    $listMapper = null;
    $filterMapper = null;

    $actionName = $this->module->getActionName() ? : "configureListMapper";

    if(AustralTools::usedImplements(get_class($this->module->getAdmin()), AdminModuleInterface::class) && $actionName === "configureListMapper") {
      $listMapper = $this->createListMapper();
      $filterMapper = $this->createFilterMapper();
    }

    $listAdminEvent = new ListAdminEvent(
      $this,
      $this->templateParameters,
      $actionName,
      $listMapper,
      $filterMapper
    );
    $this->debug->stopWatchLap("austral.admin.handler.list");
    $this->module->getAdmin()->dispatch(ListAdminEvent::EVENT_START, $listAdminEvent);
    if(method_exists($this->module->getAdmin(), $actionName))
    {
      $this->module->getAdmin()->$actionName($listAdminEvent);
    }

    if($filterMapper)
    {
      $this->executeFilterMapper($listAdminEvent);
    }

    if($listMapper)
    {
      $australPagination = $this->getSession()->get('austral_pagination', array());
      if(!array_key_exists($this->module->getModulePath(), $australPagination))
      {
        $australPagination[$this->module->getModulePath()] = array();
      }
      if(($page = $this->request->query->get('page')))
      {
        $pages = strpos($page, ",") ? explode(",", $page) : array($page);
        foreach($pages as $onePage)
        {
          $isSave = false;
          if(strpos($onePage, "-"))
          {
            list($section, $numPage) = explode("-", $onePage);
            if($listMapper->sectionExist($section))
            {
              $isSave = true;
              $australPagination[$this->module->getModulePath()][$section] = $numPage;
            }
          }
          else
          {
            $numPage = $onePage;
          }
          if(!$isSave)
          {
            $listMapper->getSection("default")->setNumPage($numPage);
            $australPagination[$this->module->getModulePath()]["default"] = $numPage;
          }
        }
      }
      foreach($australPagination[$this->module->getModulePath()] as $section => $numPage)
      {
        $listMapper->getSection($section)->setNumPage($numPage);
      }
      $this->getSession()->set('austral_pagination', $australPagination);
    }
    $this->module->getAdmin()->dispatch(ListAdminEvent::EVENT_END, $listAdminEvent);
    if($listMapper)
    {
      $this->module->getAdmin()->dispatch(ListAdminEvent::EVENT_HYDRATE, $listAdminEvent);
    }
    $this->debug->stopWatchStop("austral.admin.handler.list");
    return $this;
  }

  /**
   * @return ListMapper
   */
  protected function createListMapper(): ListMapper
  {
    /** @var ListMapper $listMapper */
    $listMapper = $this->container->get('austral.list.mapper');
    $listMapper->setTranslateDomain("austral")
      ->setTitle("pages.list.title")
      ->setSubTitle("pages.list.subTitle");

    $dataHydrateClass = $this->module->getDataHydrateClass();
    /** @var DataHydrateInterface $dataHydrate */
    $dataHydrate = new $dataHydrateClass();
    $dataHydrate->setDispatcher($this->dispatcher);
    if(method_exists($dataHydrate, "setEntityManager"))
    {
      $dataHydrate->setEntityManager($this->module->getEntityManager(), $this->module->getQueryBuilder());
    }
    $listMapper->setDataHydrate($dataHydrate);
    return $listMapper;
  }

  /**
   * @return FilterMapper
   */
  protected function createFilterMapper(): FilterMapper
  {
    return $this->container->get('austral.filter.mapper')
      ->setKeyname($this->module->getModulePath())
      ->setEntityManager($this->module->getEntityManager());
  }

  /**
   * @param FilterEventInterface $filterEvent
   *
   * @return $this
   * @throws Exception
   */
  protected function executeFilterMapper(FilterEventInterface $filterEvent): AdminHandler
  {
    if(method_exists($this->module->getAdmin(), "configureFilterMapper"))
    {
      $this->module->getAdmin()->configureFilterMapper($filterEvent);
    }
    if($filterMapper = $filterEvent->getFilterMapper())
    {
      if($this->request->attributes->get('_route') === "austral_admin_module_filter_delete")
      {
        $filterMapper->cleanFilters($this->request->attributes->get("filterName"), $this->request->attributes->get("filterElement"));
        $this->redirectUrl = $this->module->generateUrl("list");
        return $this;
      }
      else
      {
        $filterMapper->execute();
      }
    }
    return $this;
  }

  /**
   * @param string $actionKey
   * @param string $id
   *
   * @return $this
   * @throws Exception
   */
  public function action(string $actionKey, string $id): AdminHandler
  {
    $this->debug->stopWatchStart("austral.admin.handler.action", $this->debugContainer);
    $actionName = $this->module->getMethodByActionKey($actionKey);

    $actionAdminEvent = new ActionAdminEvent(
      $this,
      $this->templateParameters,
      $actionKey,
      $id !== "list" ? $this->retreiveObjectOrCreate($id) : null
    );
    $this->debug->stopWatchLap("austral.admin.handler.action");
    $this->module->getAdmin()->dispatch(ActionAdminEvent::EVENT_START, $actionAdminEvent);
    if(method_exists($this->module->getAdmin(), $actionName))
    {
      $this->module->getAdmin()->$actionName($actionAdminEvent);
      if($redirectUrl = $actionAdminEvent->getRedirectUrl())
      {
        $this->redirectUrl = $redirectUrl;
      }
    }
    $this->module->getAdmin()->dispatch(ActionAdminEvent::EVENT_END, $actionAdminEvent);
    $this->debug->stopWatchStop("austral.admin.handler.action");
    return $this;
  }

  /**
   * @param string|int $id
   *
   * @return $this
   * @throws Exception
   */
  public function form(string $id): AdminHandler
  {
    $this->module->getAdmin()->setCurrentLanguage($this->request->attributes->get('language', $this->request->getLocale()));
    $this->debug->stopWatchStart("austral.admin.handler.form", $this->debugContainer);
    /**
     * Init Form Type is Edit or Create form
     *
     */

    $isPost = $this->request->getMethod() === 'POST';
    $formTypeAction = $id != "create" ? "edit": "create";
    $this->debug->stopWatchStart("austral.admin.handler.form.retreive_object", $this->debugContainer);
    $object = $this->retreiveObjectOrCreate($id, ($formTypeAction === "create"));

    $this->debug->stopWatchStop("austral.admin.handler.form.retreive_object");
    if($object instanceof TranslateMasterInterface)
    {
      $object->setCurrentLanguage($this->request->attributes->get('language', $this->request->getLocale()));
      if(!$object->getTranslateByLanguage($object->getLanguageCurrent()) && !$object->getIsCreate())
      {
        $translateReferent = $object->getTranslateReferent();
        $duplicateAdminEvent = $this->duplicateObject($translateReferent, false);
        $duplicateAdminEvent->getDuplicateObject()->setLanguage($object->getLanguageCurrent());
        $object->setTranslateCurrent($duplicateAdminEvent->getDuplicateObject());
        $this->module->getEntityManager()->update($object);
        $this->redirectUrl = $this->module->generateUrl("edit", array('id'=>$object->getId()));
        $this->addFlash("success",
          $this->getTranslate()->trans(
            "translate.status.success",
            array(),
            "austral"
          )
        );
        return $this;
      }
    }

    if($object instanceof ComponentsInterface)
    {
      $this->debug->stopWatchStart("austral.admin.handler.form.content_block_container", $this->debugContainer);
      $this->container->get('austral.content_block.content_block_container')
        ->setCurrentLanguage($this->request->attributes->get('language', $this->request->getLocale()))
        ->initComponentByObject($object);
      $this->debug->stopWatchStop("austral.admin.handler.form.content_block_container");
    }


    $this->debug->stopWatchStart("austral.admin.handler.form.mapper.init", $this->debugContainer);
    /** @var FormMapper $formMapper */
    $formMapper = $this->container->get('austral.form.mapper');
    $formMapper->setFieldsMapping($this->module->getEntityManager()->getFieldsMappingAll())
      ->setPathToTemplateDefault("@AustralAdmin/Form/Components/Fields")
      ->setName("form_austral")
      ->setObject($object)
      ->setFormTypeAction($formTypeAction)
      ->setTranslateDomain("austral")
      ->setRequestMethod($this->request->getMethod())
      ->setModule($this->module);
    $this->debug->stopWatchStop("austral.admin.handler.form.mapper.init");

    if($this->getSession()->has("austral_form"))
    {
      $formDataSession = $this->getSession()->get("austral_form", array());
      $this->getSession()->remove("austral_form");
      $formMapper->setFormStatus($formDataSession['status'])->setFormSend($formDataSession['send']);
    }

    $this->debug->stopWatchStart("austral.admin.handler.form.type.init", $this->debugContainer);
    /** @var FormTypeInterface $formType */
    $formType = $this->container->get('austral.form.type.master')
      ->setClass($this->module->getEntityManager()->getClass())
      ->setFormMapper($formMapper);
    $this->debug->stopWatchStop("austral.admin.handler.form.type.init");


    $this->debug->stopWatchStart("austral.admin.handler.form.init", $this->debugContainer);
    $formAdminEvent = new FormAdminEvent(
      $this,
      $this->templateParameters,
      $formMapper,
    );

    $formEvent = new FormEvent($formMapper);
    $this->dispatcher->dispatch($formEvent, FormEvent::EVENT_AUSTRAL_FORM_ADD_AUTO_FIELDS_BEFORE);

    $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_START, $formAdminEvent);
    $this->debug->stopWatchStop("austral.admin.handler.form.init");
    $this->debug->stopWatchStart("austral.admin.handler.form.mapper.config", $this->debugContainer);
    $this->module->getAdmin()->configureFormMapper($formAdminEvent);
    $this->debug->stopWatchStop("austral.admin.handler.form.mapper.config");

    $this->dispatcher->dispatch($formEvent, FormEvent::EVENT_AUSTRAL_FORM_ADD_AUTO_FIELDS_AFTER);
    /** @var Form $form */
    $form = $this->container->get('form.factory')->create(get_class($formType), $formMapper->getObject());
    $formEvent->setForm($form);
    $formAdminEvent->setForm($form);
    $this->dispatcher->dispatch($formEvent, FormEvent::EVENT_AUSTRAL_FORM_INIT_END);
    if($isPost)
    {
      $this->debug->stopWatchStart("austral.admin.handler.form.post", $this->debugContainer);
      $formMapper->setFormStatus(null)->setFormSend(true);
      $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_HANDLE_REQUEST_BEFORE, $formAdminEvent);
      $form->handleRequest($this->request);
      $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_HANDLE_REQUEST_AFTER, $formAdminEvent);
      if($form->isSubmitted()) {
        $formMapper->setObject($form->getData());
        $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_AUSTRAL_FORM_VALIDATE, $formAdminEvent);

        $this->debug->stopWatchStart("austral.admin.handler.form.dispatch.validate", $this->debugContainer);
        $this->dispatcher->dispatch($formEvent, FormEvent::EVENT_AUSTRAL_FORM_VALIDATE);
        $this->debug->stopWatchStop("austral.admin.handler.form.dispatch.validate");

        if($form->isValid() && $this->module->getAdmin()->formIsValidate())
        {
          $formMapper->setFormStatus("success");
          $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_UPDATE_BEFORE, $formAdminEvent);
          $this->debug->stopWatchStart("austral.admin.handler.form.update", $this->debugContainer);
          $this->dispatcher->dispatch($formEvent, FormEvent::EVENT_AUSTRAL_FORM_UPDATE_BEFORE);
          $this->module->getEntityManager()->update($formMapper->getObject(), false);
          $this->dispatcher->dispatch($formEvent, FormEvent::EVENT_AUSTRAL_FORM_UPDATE_AFTER);
          $this->debug->stopWatchStart("austral.admin.handler.form.update", $this->debugContainer);
          $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_UPDATE_AFTER, $formAdminEvent);
          if($formMapper->getFormStatus() == "success")
          {
            $this->module->getEntityManager()->flush();
          }
          $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_FLUSH_AFTER, $formAdminEvent);
          $this->httpCacheClear($formMapper->getObject());
        }
        else
        {
          $formMapper->setFormStatus("error");
        }
      }
      else
      {
        $formMapper->setFormStatus("error");
      }
      $this->addFlash($formMapper->getFormStatus(),
        $this->getTranslate()->trans(
          "form.status.{$formMapper->getFormStatus()}",
          array('%name%' => $object->__toString()),
          "austral"
        )
      );
      if($formMapper->getFormStatus() == "success")
      {
        $this->redirectUrl = $this->module->generateUrl("edit", array('id'=>$object->getId()));
      }
      $this->getSession()->set("austral_form", array(
          "send"    =>  $formMapper->getFormSend(),
          "status"  =>  $formMapper->getFormStatus()
        )
      );
      $this->debug->stopWatchStop("austral.admin.handler.form.post");
    }
    $this->module->getAdmin()->dispatch(FormAdminEvent::EVENT_END, $formAdminEvent);
    $this->debug->stopWatchStop("austral.admin.handler.form");
    return $this;
  }

  /**
   * @param string|int $id
   *
   * @return BaseAdminHandler
   * @throws Exception
   */
  public function duplicate(string $id): AdminHandler
  {
    try {
      $duplicateManagerEvent = $this->duplicateObject($this->retreiveObjectOrCreate($id));
      $moduleObject = $this->module;
      /** @var DomainFilterMapping $domainFilter */
      if($domainFilter = $this->container->get("austral.entity.mapping")->getEntityClassMapping($duplicateManagerEvent->getDuplicateObject()->getClassnameForMapping(), DomainFilterMapping::class))
      {
        if($domainFilter->getAutoDomainId())
        {
          $children = $moduleObject->getParent()->getChildren();
          /** @var Module $child */
          foreach($children as $child)
          {
            if($child->getFilterDomainId() === $duplicateManagerEvent->getDuplicateObject()->getDomainId())
            {
              $moduleObject = $child;
            }
          }
        }
      }

      $this->redirectUrl = $this->generateUrl("austral_admin_module_form_edit", array(
          'modulePath'  =>  $moduleObject->getModulePath(),
          "id"  =>  $duplicateManagerEvent->getDuplicateObject()->getId()
        )
      );
      $this->addFlash("success",
        $this->getTranslate()->trans(
          "duplicate.status.success",
          array('%name%' => $duplicateManagerEvent->getSourceObject()->__toString()),
          "austral"
        )
      );
    } catch (\Exception $e) {
      $this->addFlash("error",
        $this->getTranslate()->trans(
          "duplicate.status.exception",
          array(),
          "austral"
        )
      );
      throw $e;
    }
    return $this;
  }

  /**
   * @param EntityInterface $object
   * @param bool $flush
   *
   * @return DuplicateAdminEvent
   */
  protected function duplicateObject(EntityInterface $object, bool $flush = true): DuplicateAdminEvent
  {
    $duplicateManagerEvent = new DuplicateAdminEvent($this, $object, null, $flush);
    $this->module->getAdmin()->dispatch(DuplicateAdminEvent::EVENT_START, $duplicateManagerEvent);
    $duplicateObject = $this->module->getEntityManager()->duplicate($duplicateManagerEvent->getSourceObject());
    $duplicateManagerEvent->setDuplicateObject($duplicateObject);
    $this->module->getAdmin()->dispatch(DuplicateAdminEvent::EVENT_END, $duplicateManagerEvent);
    $this->module->getEntityManager()->update($duplicateObject, $flush);
    return $duplicateManagerEvent;
  }

  /**
   * @var string $format
   *
   * @return StreamedResponse
   * @throws Exception
   */
  public function download(string $format): StreamedResponse
  {
    $this->debug->stopWatchStart("austral.admin.handler.download", $this->debugContainer);
    if(!$this->module->downloadFormatIsDefined($format))
    {
      throw new \Exception("Download format {$format} is not enabled for {$this->module->getName()} module");
    }

    $listMapper = $this->createListMapper();
    $filterMapper = $this->createFilterMapper();

    $actionName = $this->module->getActionName() ? : "configurationDownload";
    $downloadAdminEvent = new DownloadAdminEvent(
      $this,
      $listMapper,
      $filterMapper
    );

    $this->executeFilterMapper($downloadAdminEvent);

    $this->debug->stopWatchLap("austral.admin.handler.download");
    $this->module->getAdmin()->dispatch(DownloadAdminEvent::EVENT_START, $downloadAdminEvent);

    if(method_exists($this->module->getAdmin(), $actionName))
    {
      $this->module->getAdmin()->$actionName($downloadAdminEvent);
    }
    else
    {
      throw new \Exception("configurationDownload method in your admin for {$this->module->getName()} module is not defined");
    }
    $listMapper->generate();
    $this->module->getAdmin()->dispatch(DownloadAdminEvent::EVENT_END, $downloadAdminEvent);

    /** @var Download $download */
    $download = $this->container->get("austral.admin.download")
      ->setListMapper($listMapper)
      ->setFormat($format)
      ->setFilename($this->module->getName());

    $this->debug->stopWatchStop("austral.admin.handler.download");

    $response = new StreamedResponse(
      function () use ($download) {
        $download->generate();
      },
      200,
      array()
    );
    $dispositionHeader = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $download->getFilename()
    );
    $response->headers->set('Content-Type', "{$download->getContentType()}; charset=utf-8");
    $response->headers->set('Pragma', 'public');
    $response->headers->set('Cache-Control', 'max-age=0');
    $response->headers->set('Content-Disposition', $dispositionHeader);
    return $response;
  }

  /**
   * @param string|int $id
   *
   * @return BaseAdminHandler
   * @throws Exception
   */
  public function delete(string $id): AdminHandler
  {
    $this->module->getAdmin()->setContainer($this->container);
    $deleteAdminEvent = new DeleteAdminEvent($this, $this->retreiveObjectOrCreate($id));
    $this->module->getAdmin()->dispatch(DeleteAdminEvent::EVENT_START, $deleteAdminEvent);

    $this->httpCacheClear($deleteAdminEvent->getObject());

    $this->module->getEntityManager()->delete($deleteAdminEvent->getObject());
    $this->module->getAdmin()->dispatch(DeleteAdminEvent::EVENT_END, $deleteAdminEvent);
    $this->redirectUrl = $this->generateUrl("austral_admin_module_index", array('modulePath'=>$this->module->getModulePath()));

    if($this->container->has('austral.notify.push'))
    {
      /** @var Push $push */
      $push = $this->container->get('austral.notify.push');
      $push->add(Push::TYPE_MERCURE, array('topics'=>array($this->module->generateUrl("edit", array('id'=>$id))), "values" => array(
        'type'            =>  "remove",
        "name"            =>  $deleteAdminEvent->getObject()->__toString(),
        "url"             =>  $this->redirectUrl,
        "userTabId"       =>  $this->userTabId,
        "redirect"        =>  true,
        "reloadElements"  =>  "container"
      )))->push();
    }
    return $this;
  }

  /**
   * @return BaseAdminHandler
   * @throws Exception
   */
  public function truncate(): AdminHandler
  {
    $this->module->getAdmin()->setContainer($this->container);

    $listMapper = $this->createListMapper();
    $filterMapper = $this->createFilterMapper();

    $truncateAdminEvent = new TruncateAdminEvent(
      $this,
      $listMapper,
      $filterMapper
    );
    $this->module->getAdmin()->dispatch(TruncateAdminEvent::EVENT_START, $truncateAdminEvent);
    $this->executeFilterMapper($truncateAdminEvent);

    try {

      /** @var FilterMapperInterface|Filter|null $filter */
      $filter = $filterMapper->filter("default");
      if($filter->hasFilterValue() || $this->module->getFilterDomainId())
      {
        $listMapper->generate();
        /** @var Section $section */
        foreach($listMapper->getSection("default")->getObjects() as $object)
        {
          $this->module->getEntityManager()->delete($object, false);
        }
        $this->module->getEntityManager()->flush();
      }
      else
      {
        $this->module->getEntityManager()->truncate();
      }
      $this->addFlash("success",
        $this->getTranslate()->trans(
          "truncate.status.success",
          array(),
          "austral"
        )
      );
    }
    catch (\Exception $e) {
      $this->addFlash("error",
        $this->getTranslate()->trans(
          "truncate.status.exception",
          array(),
          "austral"
        )
      );
    }

    $this->module->getAdmin()->dispatch(TruncateAdminEvent::EVENT_END, $truncateAdminEvent);
    $this->redirectUrl = $this->generateUrl("austral_admin_module_index", array('modulePath'=>$this->module->getModulePath()));
    return $this;
  }

  /**
   * @param string|int $id
   *
   * @return BaseAdminHandler
   * @throws Exception
   */
  public function changeValue(string $id, string $fieldname, $value = null): AdminHandler
  {
    $this->debug->stopWatchStart("austral.admin.handler.changeValue", $this->debugContainer);
    try {
      $this->module->getAdmin()->setContainer($this->container);
      $changeValueManagerEvent = new ChangeValueAdminEvent($this, $this->retreiveObjectOrCreate($id), $fieldname, $value);
      $this->debug->stopWatchLap("austral.admin.handler.changeValue");
      $this->module->getAdmin()->dispatch(ChangeValueAdminEvent::EVENT_START, $changeValueManagerEvent);
      $this->module->getEntityManager()->update($changeValueManagerEvent->getObject());
      $this->module->getAdmin()->dispatch(ChangeValueAdminEvent::EVENT_END, $changeValueManagerEvent);

      $this->httpCacheClear($changeValueManagerEvent->getObject());

      $this->addFlash("success",
        $this->getTranslate()->trans(
          "changeValue.status.success",
          array('%name%' => $changeValueManagerEvent->getObject()->__toString()),
          "austral"
        )
      );
    } catch(\Exception $e) {
      $this->addFlash("error",
        $this->getTranslate()->trans(
          "changeValue.status.exception",
          array(),
          "austral"
        )
      );
    }
    $this->redirectUrl = $this->generateUrl("austral_admin_module_index", array('modulePath'=>$this->module->getModulePath()));
    $this->debug->stopWatchStop("austral.admin.handler.changeValue");
    return $this;
  }

  /**
   * @return $this
   */
  public function sortable(): AdminHandler
  {
    $this->debug->stopWatchStart("austral.admin.handler.sortable", $this->debugContainer);
    try {

      $actionName = $this->module->getActionName() ? : "configurationSortable";

      /** @var array $positions */
      $positions = json_decode($this->request->request->get('positions', "{}"),true);

      $sortableAdminEvent = new SortableAdminEvent($this, $this->module->getEntityManager(), $positions, null);
      $this->debug->stopWatchLap("austral.admin.handler.sortable");
      $this->module->getAdmin()->dispatch(SortableAdminEvent::EVENT_START, $sortableAdminEvent);
      if(method_exists($this->module->getAdmin(), $actionName))
      {
        $this->module->getAdmin()->$actionName($sortableAdminEvent);
      }
      $this->module->getAdmin()->dispatch(SortableAdminEvent::EVENT_END, $sortableAdminEvent);

      $this->httpCacheClear();
      $this->addFlash("success",
        $this->getTranslate()->trans(
          "sortable.status.success",
          array(),
          "austral"
        )
      );
    } catch(\Exception $e) {
      $this->addFlash("error",
        $this->getTranslate()->trans(
          "changeValue.status.exception",
          array(),
          "austral"
        )
      );
    }
    $this->redirectUrl = $this->generateUrl("austral_admin_module_index", array('modulePath'=>$this->module->getModulePath()));
    $this->debug->stopWatchStop("austral.admin.handler.sortable");
    return $this;
  }

  /**
   * httpCacheClear
   *
   * @param EntityInterface|null $object
   * @return AdminHandler
   */
  public function httpCacheClear(?EntityInterface $object = null): AdminHandler
  {
    if($this->container->get('austral.cache.config')->get('clearAuto'))
    {
      $this->debug->stopWatchStart("austral.admin.handler.http_cache.clear", $this->debugContainer);
      $httpCacheClearAdminEvent = new HttpCacheClearAdminEvent($this, $object);
      $this->module->getAdmin()->dispatch(HttpCacheClearAdminEvent::EVENT_START, $httpCacheClearAdminEvent);
      if($httpCacheClearAdminEvent->getEnabled())
      {
        $httpCacheEvent = new HttpCacheEvent($httpCacheClearAdminEvent->getUri());
        $this->dispatcher->dispatch($httpCacheEvent, HttpCacheEvent::EVENT_CLEAR_HTTP_CACHE);
      }
      $this->module->getAdmin()->dispatch(HttpCacheClearAdminEvent::EVENT_END, $httpCacheClearAdminEvent);
      $this->debug->stopWatchStop("austral.admin.handler.http_cache.clear");
    }
    return $this;
  }

  /**
   * @param string $id
   * @param false $create
   *
   * @return EntityInterface
   * @throws Exception
   */
  protected function retreiveObjectOrCreate(string $id, bool $create = false): EntityInterface
  {
    /**
     * Check Admin  is AdminModuleInterface::class
     */
    if(!AustralTools::usedImplements(get_class($this->module->getAdmin()), AdminModuleInterface::class))
    {
      throw $this->createNotFoundException(sprintf("This page is not implements : %s",AdminModuleInterface::class));
    }

    if($create)
    {
      return  $this->module->getEntityManager()->create();
    }

    /**
     * Retreive Object by id
     */
    if (!$object = $this->module->getEntityManager()->retreiveById($id))
    {
      throw new Exception(sprintf("%s is not found !!!", $this->module->getEntityManager()->getClass()), 404);
    }
    return $object;
  }

}