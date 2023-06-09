<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Controller;

use Austral\AdminBundle\Handler\Interfaces\AdminHandlerInterface;
use Austral\HttpBundle\Controller\HttpController;

use Austral\AdminBundle\Services\Guideline;
use Austral\AdminBundle\Template\TemplateParameters;
use Austral\HttpBundle\Handler\Interfaces\HttpHandlerInterface;
use Austral\NotifyBundle\Mercure\Mercure;
use Austral\NotifyBundle\Notification\Push;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\MessageCatalogue;

Use \Exception;
/**
 * Austral Admin Controller.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class AdminController extends HttpController
{

  /**
   * @var HttpHandlerInterface|AdminHandlerInterface
   */
  protected HttpHandlerInterface $handlerManager;

  /**
   * @param Request $request
   *
   * @return JsonResponse
   * @throws Exception
   */
  public function ping(Request $request): JsonResponse
  {
    /** @var Mercure $mercure */
    $mercure = $this->container->get('austral.notify.mercure');
    $array = $mercure->listSubscribes();

    $userTabId = $request->request->get('userTabId');
    $uri = $request->query->get('uri') ? : $request->request->get('uri');
    $topicInit = null;
    if($uri)
    {
      $topicInit = $mercure->topicWithDomain($uri);
    }

    /** @var Push $push */
    $push = $this->container->get('austral.notify.push');

    $topics = array();
    foreach($array["subscriptions"] as $subscription)
    {
      $topicKey = $subscription["topic"];
      preg_match("^.*\/(user-tab)-(\w{8}-\w{4}-\w{4}-\w{4}-\w{12})^", $subscription["topic"], $matches);
      if(array_key_exists(1, $matches) && array_key_exists(2, $matches))
      {
        $topicKey = "user-tab";
        $subscription['pushTopic'] = "user-tab-{$matches[2]}";
        $subscription["userTabId"] = $matches[2];
        $topics[$topicKey][$subscription["subscriber"]] = $subscription;
      }
      else
      {
        $topics[$topicKey][$subscription["subscriber"]] = $subscription;
      }
    }

    $status = "stop";
    foreach($topics as $topic => $subscriptions)
    {
      preg_match("^.*\/(.*)\/form^", $topic, $matches);
      if($matches && (count($subscriptions) > 1))
      {
        if($userTabId)
        {
          foreach (array_keys($subscriptions) as $uuid)
          {
            if($topics['user-tab'][$uuid]['userTabId'] === $userTabId)
            {
              $push->add(Push::TYPE_MERCURE, array('topics'=>array($topics['user-tab'][$uuid]['pushTopic']), "values" => array(
                'type'            =>  "multi-user",
              )))->push();
              $status  = "run";
            }
          }
        }
        elseif($topicInit === $topic)
        {
          $push->add(Push::TYPE_MERCURE, array('topics'=>array($uri), "values" => array(
            'type'            =>  "multi-user",
          )))->push();
          $status  = "run";
        }
      }
    }
    return new JsonResponse(array('status'=>$status));
  }

  /**
   * @param AuthenticationUtils $authenticationUtils
   * @param Request $request
   *
   * @return RedirectResponse|Response
   */
  public function authenticated(AuthenticationUtils $authenticationUtils, Request $request)
  {
    if ($this->getUser())
    {
      return new RedirectResponse($this->generateUrl("austral_admin_index"), 302);
    }

    /** @var TemplateParameters $templateParameters */
    $templateParameters = $this->container->get('austral.admin.template');

    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    $templateParameters->addParameters("project", $this->container->get("austral.admin.config")->getConfig("project"))
      ->addParameters("last_username", $lastUsername)
      ->addParameters("error", $error);
    /** @var AuthenticationUtils $authenticationUtils */
    return $this->render('@AustralAdmin/Authenticated/login.html.twig',
      $templateParameters->__serialize()
    );
  }

  /**
   * @return Response
   * @throws Exception
   */
  public function index(): Response
  {
    $this->handlerManager->index();
    if($redirectUrl = $this->handlerManager->getRedirectUrl())
    {
      return $this->redirect($redirectUrl);
    }
    return $this->render(
      $this->handlerManager->getTemplateParameters()->getPath(),
      $this->handlerManager->getTemplateParameters()->__serialize()
    );
  }

  /**
   * @param string $id
   * @param string $actionKey
   *
   * @return Response
   */
  public function action(string $id, string $actionKey): Response
  {
    $this->handlerManager->action($actionKey, $id);
    if($redirectUrl = $this->handlerManager->getRedirectUrl())
    {
      return $this->redirect($redirectUrl);
    }
    return $this->render(
      $this->handlerManager->getTemplateParameters()->getPath(),
      $this->handlerManager->getTemplateParameters()->__serialize()
    );
  }

  /**
   * @param string $id
   *
   * @return RedirectResponse|Response
   */
  public function form(string $id = "create")
  {
    $this->handlerManager->form($id);
    if($redirectUrl = $this->handlerManager->getRedirectUrl())
    {
      return $this->redirect($redirectUrl);
    }
    return $this->render(
      $this->handlerManager->getTemplateParameters()->getPath(),
      $this->handlerManager->getTemplateParameters()->__serialize()
    );
  }

  /**
   * @param string $id
   *
   * @return RedirectResponse
   */
  public function duplicate(string $id): RedirectResponse
  {
    $this->handlerManager->duplicate($id);
    return $this->redirect($this->handlerManager->getRedirectUrl());
  }

  /**
   * @param string $format
   *
   * @return StreamedResponse
   */
  public function download(string $format): StreamedResponse
  {
    return $this->handlerManager->download($format);
  }

  /**
   * @param string $id
   *
   * @return RedirectResponse
   */
  public function delete(string $id): RedirectResponse
  {
    $this->handlerManager->delete($id);
    return $this->redirect($this->handlerManager->getRedirectUrl());
  }

  /**
   * @return RedirectResponse
   */
  public function truncate(): RedirectResponse
  {
    $this->handlerManager->truncate();
    return $this->redirect($this->handlerManager->getRedirectUrl());
  }

  /**
   * @param string $id
   * @param string $fieldname
   * @param $value
   *
   * @return RedirectResponse
   */
  public function changeValue(string $id, string $fieldname, $value): RedirectResponse
  {
    $this->handlerManager->changeValue($id, $fieldname, $value);
    return $this->redirect($this->handlerManager->getRedirectUrl());
  }

  /**
   * @return RedirectResponse
   */
  public function sortable(): RedirectResponse
  {
    $this->handlerManager->sortable();
    return $this->redirect($this->handlerManager->getRedirectUrl());
  }

  /**
   * @param string|null $type
   *
   * @return Response
   * @throws Exception
   */
  public function guideline(string $type = null): Response
  {
    /** @var TemplateParameters $templateParameters */
    $templateParameters = $this->container->get('austral.admin.template');
    $templateParameters->initTemplate();
    $templateParameters->setPath("@AustralAdmin/Guideline/guideline.html.twig");


    /** @var Guideline $guideline */
    $guideline = $this->container->get('austral.admin.guideline');
    if($type == "colors")
    {
      $templateParameters->setPath("@AustralAdmin/Guideline/guideline-colors.html.twig");
      $templateParameters->setParameters($guideline->retreiveColors());
    }
    if($type == "fonts")
    {
      $templateParameters->setPath("@AustralAdmin/Guideline/guideline-fonts.html.twig");
      $templateParameters->setParameters($guideline->retreiveFonts());
    }

    $templateParameters->addParameters("user", $this->getUser());
    $templateParameters->addParameters("current", $type != null ? $type : "home");
    return $this->render(
      $templateParameters->getPath(),
      $templateParameters->__serialize()
    );
  }

  /**
   * @param string $_locale
   *
   * @return Response
   */
  public function translationJson(string $_locale): Response
  {
    $return = array();
    $domains = array("austral", "message");

    /** @var MessageCatalogue $catalogue */
    if($catalogue = $this->getTranslate()->getCatalogue($_locale))
    {
      foreach($domains as $domain)
      {
        if($messages = $catalogue->all($domain))
        {
          foreach($messages as $key => $message)
          {
            $return["{$domain}.{$key}"] = $message;
          }
        }
      }
    }
    $json = json_encode($return);
    $translations = $this->renderView(
      '@AustralAdmin/Layout/translation.js.twig',
      array(
        'json' => $json,
        "language"  =>  $_locale
      )
    );
    return new Response($translations, 200,
      array('Content-Type' => 'text/javascript')
    );
  }

  /**
   * @return Response
   */
  public function popinSelectLinks(): Response
  {
    $this->container->get('austral.http.domains.management')->initialize();
    $urlParameterManagement = $this->container->get('austral.seo.url_parameter.management')->initialize();

    return $this->render("@AustralDesign/Components/Popin/templates/select-links-content.html.twig", array(
      "urlsByDomains" => $urlParameterManagement->getUrlParametersByDomainsWithTree()
    ));
  }

  /**
   * popinIcons
   * @return Response
   */
  public function popinGraphicItems(): Response
  {
    if($this->container->has('austral.graphic_items.management'))
    {
      $graphicItemsManagement = $this->container->get('austral.graphic_items.management')->init();
      $graphicItems = $graphicItemsManagement->getPictos(true);
    }
    return $this->render("@AustralDesign/Components/Popin/templates/graphic-items.html.twig", array(
      "graphicItems"  =>  $graphicItems
    ));
  }

}
