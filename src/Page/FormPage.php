<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Page;

use Austral\AdminBundle\Event\AdminEventDispatcher;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\FormAdminEvent;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\View\AdminView;
use Austral\Contracts\Entity\EntityInterface;
use Austral\Contracts\Translate\TranslateMasterInterface;
use Austral\FormBundle\Form\Type\AustralFormType;
use Austral\FormBundle\Handler\FormHandler;
use Austral\FormBundle\Handler\FormResult;
use Austral\FormBundle\Mapper\FormMapper;
use Austral\FormBundle\Mapper\FormMapperFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Austral Form Page.
 *
 * The form of an entity of a module (create, edit), on form-bundle: the mapper configured by
 * AdminEvents::FORM_CONFIGURE, the form handled by FormHandler, the entity saved by the entity manager. Called by
 * AdminHandler::create() and AdminHandler::edit().
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class FormPage
{
  public const string TEMPLATE = '@AustralAdmin/module/form.html.twig';
  public const string FORM_NAME = 'form';

  /**
   * Name of the submit buttons, "back" goes back to the list after the save.
   */
  public const string SAVE_PARAMETER = '_save';

  /**
   * __construct
   *
   * @param FormMapperFactory    $mapperFactory
   * @param FormFactoryInterface $formFactory
   * @param FormHandler          $formHandler
   * @param AdminEventDispatcher $dispatcher
   * @param EntityQuery          $query
   * @param AdminUrlGenerator    $urls
   * @param ListUrlStore         $listUrls
   *
   * @return void
   */
  public function __construct(
    private readonly FormMapperFactory $mapperFactory,
    private readonly FormFactoryInterface $formFactory,
    private readonly FormHandler $formHandler,
    private readonly AdminEventDispatcher $dispatcher,
    private readonly EntityQuery $query,
    private readonly AdminUrlGenerator $urls,
    private readonly ListUrlStore $listUrls,
  ) {
  }

  /**
   * handle
   * Saves the form and redirects (to the list or to the form), else the page of the form.
   *
   * @param Module      $module
   * @param Request     $request
   * @param string|null $id      Null to create an entity
   *
   * @return Response|AdminView
   *
   * @throws \LogicException when the form of the module has no field
   */
  public function handle(Module $module, Request $request, ?string $id = null): Response|AdminView
  {
    $create = null === $id;
    $object = $create ? $this->newObject($module) : $this->query->find($module, $request, $id);
    if ($object instanceof TranslateMasterInterface && null !== $module->getLanguage())
    {
      $object->setCurrentLanguage($module->getLanguage());
    }

    $mapper = $this->mapperFactory->create(self::FORM_NAME, $object::class, $object, function (FormMapper $mapper) use ($module, $request, $object, $create): void {
      $mapper->setTranslationDomain(ListConfigurator::TRANSLATION_DOMAIN);
      $this->dispatcher->dispatch(new FormAdminEvent($module, $request, $mapper, $object, $create), AdminEvents::FORM_CONFIGURE);
    });
    if (!$mapper->getFields())
    {
      throw new \LogicException(\sprintf('The form of the admin module "%s" has no field: add them in %s::configureFormMapper().', $module->getPath(), $module->hasAdmin() ? $module->getAdmin()::class : 'its Admin'));
    }
    $form = $this->formFactory->createNamed($mapper->getName(), AustralFormType::class, $object, ['mapper' => $mapper]);

    $entityManager = $module->getEntityManager();
    $result = $this->formHandler->process($form, $request, function (FormInterface $form) use ($module, $request, $mapper, $object, $create, $entityManager): void {
      $this->dispatcher->dispatch(new FormAdminEvent($module, $request, $mapper, $object, $create, $form), AdminEvents::FORM_SAVE_BEFORE);
      if ($object instanceof EntityInterface)
      {
        $entityManager->update($object);
      }
      else
      {
        $entityManager->getDoctrineEntityManager()->persist($object);
        $entityManager->flush();
      }
      $this->dispatcher->dispatch(new FormAdminEvent($module, $request, $mapper, $object, $create, $form), AdminEvents::FORM_SAVE_AFTER);
    });

    if ($result->isSaved())
    {
      self::flash($request, 'success', $create ? 'flash.created' : 'flash.saved');
      $back = 'back' === $request->request->get(self::SAVE_PARAMETER) || !$module->isGranted(ModuleAction::EDIT);

      return new RedirectResponse($back ? $this->listUrls->get($module, $request) : $this->urls->object($module, ModuleAction::EDIT, $object));
    }
    if (FormResult::STATUS_INVALID === $result->getStatus())
    {
      self::flash($request, 'error', 'flash.invalid');
    }

    return new AdminView($create ? 'create' : 'edit', self::TEMPLATE, [
      'form' => $form->createView(),
      'object' => $object,
      'create' => $create,
      'title' => $create ? null : ListConfigurator::label($object),
      'list_url' => $this->listUrls->get($module, $request),
      'actions' => $this->objectActions($module, $object, $create),
    ], FormResult::STATUS_INVALID === $result->getStatus() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK, $create ? null : ListConfigurator::label($object));
  }

  /**
   * newObject
   *
   * @param Module $module
   *
   * @return object
   */
  public function newObject(Module $module): object
  {
    $object = $module->getEntityManager()->create();
    foreach ($module->getVariants() as $variant)
    {
      if (null !== $variant->create)
      {
        ($variant->create)($object);
      }
    }

    return $object;
  }

  /**
   * flash
   *
   * @param Request $request
   * @param string  $type
   * @param string  $message Translation key (domain austral)
   *
   * @return void
   */
  public static function flash(Request $request, string $type, string $message): void
  {
    if ($request->hasSession() && method_exists($session = $request->getSession(), 'getFlashBag'))
    {
      $session->getFlashBag()->add($type, $message);
    }
  }

  /**
   * objectActions
   * Actions of the page of an existing entity: duplicate and delete (POST), extra actions.
   *
   * @param Module $module
   * @param object $object
   * @param bool   $create
   *
   * @return list<array{name: string, url: string, method: string}>
   */
  private function objectActions(Module $module, object $object, bool $create): array
  {
    if ($create)
    {
      return [];
    }
    $actions = [];
    foreach (array_keys($module->getDefinition()->getExtendActions()) as $action)
    {
      if ($module->isGranted($action))
      {
        $actions[] = ['name' => $action, 'url' => $this->urls->object($module, $action, $object), 'method' => 'get'];
      }
    }
    foreach ([ModuleAction::DUPLICATE, ModuleAction::DELETE] as $action)
    {
      if ($module->isGranted($action))
      {
        $actions[] = ['name' => $action, 'url' => $this->urls->object($module, $action, $object), 'method' => 'post'];
      }
    }

    return $actions;
  }
}
