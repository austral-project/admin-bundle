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
use Austral\AdminBundle\Event\FilterAdminEvent;
use Austral\AdminBundle\Event\ListAdminEvent;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\FilterBundle\Mapper\FilterMapper;
use Austral\FilterBundle\Mapper\FilterMapperFactory;
use Austral\ListBundle\Action\Action;
use Austral\ListBundle\Column\ValueColumn;
use Austral\ListBundle\Mapper\ListMapper;
use Austral\ListBundle\Mapper\ListMapperFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral List Configurator.
 *
 * The list mapper of an entity module, with the default actions, configured by AdminEvents::LIST_CONFIGURE, and
 * its filter mapper, configured by AdminEvents::FILTER_CONFIGURE. Used by the list, the export and the toggle:
 * the toggle action accepts only the columns of this list.
 *
 * An action with the attribute data-method="post" is rendered by the admin list theme as a form with the CSRF
 * token of the admin.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ListConfigurator
{
  public const string TRANSLATION_DOMAIN = 'austral';

  /**
   * @param ListMapperFactory    $listMapperFactory
   * @param FilterMapperFactory  $filterMapperFactory
   * @param AdminEventDispatcher $dispatcher
   * @param AdminUrlGenerator    $urls
   * @param int                  $perPage
   */
  public function __construct(
    private readonly ListMapperFactory $listMapperFactory,
    private readonly FilterMapperFactory $filterMapperFactory,
    private readonly AdminEventDispatcher $dispatcher,
    private readonly AdminUrlGenerator $urls,
    private readonly int $perPage = 30,
  ) {
  }

  /**
   * @param Module  $module
   * @param Request $request
   *
   * @return ListAdminEvent The event holds the mapper, the sections and the number by page
   */
  public function configureList(Module $module, Request $request): ListAdminEvent
  {
    $event = null;
    $this->listMapperFactory->create(self::name('list', $module), ['translation_domain' => self::TRANSLATION_DOMAIN], function (ListMapper $mapper) use ($module, $request, &$event): void {
      $this->addDefaultActions($mapper, $module);
      $event = $this->dispatcher->dispatch(new ListAdminEvent($module, $request, $mapper, $this->urls, $this->perPage), AdminEvents::LIST_CONFIGURE);
      if (!$mapper->getColumns())
      {
        $mapper->add(new ValueColumn('name', ['getter' => static fn (object $item): string => self::label($item)]));
      }
    });

    /* @var ListAdminEvent $event */
    return $event;
  }

  /**
   * @param Module            $module
   * @param Request           $request
   * @param list<ListSection> $sections
   *
   * @return FilterMapper
   */
  public function configureFilters(Module $module, Request $request, array $sections): FilterMapper
  {
    return $this->filterMapperFactory->create(self::name('filter', $module), [
      'translation_domain' => self::TRANSLATION_DOMAIN,
      'reset_parameters' => array_map(static fn (ListSection $section): string => $section->getPageParameter(), $sections),
    ], function (FilterMapper $mapper) use ($module, $request): void {
      $this->dispatcher->dispatch(new FilterAdminEvent($module, $request, $mapper), AdminEvents::FILTER_CONFIGURE);
    });
  }

  /**
   * @param object $item
   *
   * @return string
   */
  public static function label(object $item): string
  {
    return $item instanceof \Stringable ? (string) $item : $item::class;
  }

  /**
   * @param string $type
   * @param Module $module
   *
   * @return string Name of a mapper: a DOM id prefix
   */
  private static function name(string $type, Module $module): string
  {
    return $type.'-'.str_replace('/', '-', $module->getPath());
  }

  /**
   * @param ListMapper $mapper
   * @param Module     $module
   *
   * @return void
   */
  private function addDefaultActions(ListMapper $mapper, Module $module): void
  {
    $urls = $this->urls;
    $post = static fn (string $confirm): array => ['data-method' => 'post', 'data-confirm' => $confirm];

    if ($module->isGranted(ModuleAction::CREATE))
    {
      $mapper->addAction(new Action(ModuleAction::CREATE, ['url' => $urls->module($module, ModuleAction::CREATE), 'picto' => 'austral-picto-plus']));
    }
    foreach ($module->getDefinition()->getDownloadFormats() as $format)
    {
      if ($module->isGranted(ModuleAction::EXPORT))
      {
        $mapper->addAction(new Action("export_{$format}", [
          'label' => 'actions.export',
          'label_parameters' => ['%format%' => strtoupper($format)],
          'url' => $urls->module($module, ModuleAction::EXPORT, ['format' => $format]),
          'picto' => 'austral-picto-download',
        ]));
      }
    }
    if ($module->isGranted(ModuleAction::TRUNCATE))
    {
      $mapper->addAction(new Action(ModuleAction::TRUNCATE, [
        'url' => $urls->module($module, ModuleAction::TRUNCATE),
        'picto' => 'austral-picto-trash',
        'attr' => $post('confirm.truncate'),
      ]));
    }

    if ($module->isGranted(ModuleAction::EDIT))
    {
      $mapper->setRowUrl(static fn (object $item): string => $urls->object($module, ModuleAction::EDIT, $item));
      $mapper->addRowAction(new Action(ModuleAction::EDIT, [
        'url' => static fn (object $item): string => $urls->object($module, ModuleAction::EDIT, $item),
        'picto' => 'austral-picto-edit',
      ]));
    }
    foreach (array_keys($module->getDefinition()->getExtendActions()) as $action)
    {
      if ($module->isGranted($action))
      {
        $mapper->addRowAction(new Action($action, [
          'url' => static fn (object $item): string => $urls->object($module, $action, $item),
        ]));
      }
    }
    if ($module->isGranted(ModuleAction::DUPLICATE))
    {
      $mapper->addRowAction(new Action(ModuleAction::DUPLICATE, [
        'url' => static fn (object $item): string => $urls->object($module, ModuleAction::DUPLICATE, $item),
        'picto' => 'austral-picto-copy',
        'attr' => $post('confirm.duplicate'),
      ]));
    }
    if ($module->isGranted(ModuleAction::DELETE))
    {
      $mapper->addRowAction(new Action(ModuleAction::DELETE, [
        'url' => static fn (object $item): string => $urls->object($module, ModuleAction::DELETE, $item),
        'picto' => 'austral-picto-trash',
        'attr' => $post('confirm.delete'),
      ]));
    }
  }
}
