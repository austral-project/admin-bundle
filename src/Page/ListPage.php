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

use Austral\AdminBundle\Event\QueryAdminEvent;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\View\AdminRenderer;
use Austral\FilterBundle\Doctrine\DoctrineFilterApplier;
use Austral\FilterBundle\Handler\FilterHandler;
use Austral\FilterBundle\Handler\FilterState;
use Austral\FilterBundle\Mapper\FilterMapper;
use Austral\FilterBundle\View\FilterViewBuilder;
use Austral\ListBundle\Pagination\Pagination;
use Austral\ListBundle\View\ListViewBuilder;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Austral List Page.
 *
 * The list of an entity module: its sections, each one with its query, filters and pagination.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ListPage
{
  public const string TEMPLATE = '@AustralAdmin/module/list.html.twig';

  /**
   * @param ListConfigurator      $configurator
   * @param EntityQuery           $query
   * @param FilterHandler         $filterHandler
   * @param DoctrineFilterApplier $filterApplier
   * @param FilterViewBuilder     $filterViewBuilder
   * @param ListViewBuilder       $listViewBuilder
   * @param AdminUrlGenerator     $urls
   * @param ListUrlStore          $listUrls
   * @param AdminRenderer         $renderer
   */
  public function __construct(
    private readonly ListConfigurator $configurator,
    private readonly EntityQuery $query,
    private readonly FilterHandler $filterHandler,
    private readonly DoctrineFilterApplier $filterApplier,
    private readonly FilterViewBuilder $filterViewBuilder,
    private readonly ListViewBuilder $listViewBuilder,
    private readonly AdminUrlGenerator $urls,
    private readonly ListUrlStore $listUrls,
    private readonly AdminRenderer $renderer,
  ) {
  }

  /**
   * @param Module  $module
   * @param Request $request
   *
   * @return Response
   */
  public function render(Module $module, Request $request): Response
  {
    $listEvent = $this->configurator->configureList($module, $request);
    $mapper = $listEvent->getMapper();
    $sections = array_values($listEvent->getSections());
    $filterMapper = $this->configurator->configureFilters($module, $request, $sections);
    $filterState = $this->filterHandler->handle($filterMapper, $request);

    $views = [];
    foreach ($sections as $section)
    {
      $queryBuilder = $this->query->queryBuilder($module, $request, QueryAdminEvent::CONTEXT_LIST, $section);
      $this->applyFilters($queryBuilder, $filterMapper, $filterState, $section);
      self::defaultOrder($queryBuilder, $module);
      $perPage = $section->perPage ?? $listEvent->getPerPage();
      [$objects, $pagination] = $this->paginate($module, $request, $queryBuilder, $section, $perPage);
      $views[$section->name] = [
        'name' => $section->name,
        'label' => $section->label,
        'list' => $this->listViewBuilder->build($mapper, $objects, $pagination),
        'total' => $pagination?->getTotal() ?? \count($objects),
      ];
    }
    $this->listUrls->save($module, $request);

    $sortField = $module->getDefinition()->getSortableField();

    return $this->renderer->render($request, 'list', self::TEMPLATE, [
      'sections' => $views,
      'filters' => $filterMapper->getGroups() ? $this->filterViewBuilder->build($filterState, $request) : [],
      'actions' => $views ? reset($views)['list']['actions'] : [],
      'sort' => null !== $sortField && $module->isGranted(ModuleAction::SORT) ? ['url' => $this->urls->module($module, ModuleAction::SORT), 'field' => $sortField] : null,
      'language' => $module->getLanguage(),
    ], $module);
  }

  /**
   * @param QueryBuilder $queryBuilder
   * @param FilterMapper $mapper
   * @param FilterState  $state
   * @param ListSection  $section
   *
   * @return void
   */
  public function applyFilters(QueryBuilder $queryBuilder, FilterMapper $mapper, FilterState $state, ListSection $section): void
  {
    $group = $mapper->has($section->name) ? $section->name : ($mapper->has(FilterMapper::DEFAULT_GROUP) ? FilterMapper::DEFAULT_GROUP : null);
    if (null !== $group)
    {
      $this->filterApplier->apply($queryBuilder, $state->getCriteria($group));
    }
  }

  /**
   * The position of a sortable module when the query has no order.
   *
   * @param QueryBuilder $queryBuilder
   * @param Module       $module
   *
   * @return void
   */
  public static function defaultOrder(QueryBuilder $queryBuilder, Module $module): void
  {
    $field = $module->getDefinition()->getSortableField();
    if (null !== $field && !$queryBuilder->getDQLPart('orderBy'))
    {
      $queryBuilder->orderBy(EntityQuery::ROOT_ALIAS.".{$field}", 'ASC');
    }
  }

  /**
   * @param Module       $module
   * @param Request      $request
   * @param QueryBuilder $queryBuilder
   * @param ListSection  $section
   * @param int          $perPage
   *
   * @return array{0: list<object>, 1: ?Pagination}
   */
  private function paginate(Module $module, Request $request, QueryBuilder $queryBuilder, ListSection $section, int $perPage): array
  {
    $entityManager = $module->getEntityManager();
    if ($perPage < 1)
    {
      /** @var list<object> $objects */
      $objects = array_values($entityManager->selectByQueryBuilder($queryBuilder));

      return [$objects, null];
    }

    $parameter = $section->getPageParameter();
    $page = max(1, $request->query->getInt($parameter, 1));
    $result = $entityManager->paginatorByQueryBuilder((clone $queryBuilder)->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage));
    if (null !== ($exception = $result->getException()))
    {
      throw $exception;
    }
    $query = $request->query->all();
    $route = $request->attributes->get('_route');
    $routeParameters = $request->attributes->get('_route_params', []);
    $url = fn (int $number): string => $this->urls->route(\is_string($route) ? $route : '', array_replace(\is_array($routeParameters) ? $routeParameters : [], $query, [$parameter => $number]));
    $pagination = new Pagination($page, $perPage, $result->getCount(), $url);
    $objects = array_values($result->getObjects());
    if ($pagination->getPage() !== $page)
    {
      $result = $entityManager->paginatorByQueryBuilder((clone $queryBuilder)->setFirstResult($pagination->getOffset())->setMaxResults($perPage));
      $objects = array_values($result->getObjects());
    }

    /* @var list<object> $objects */
    return [$objects, $pagination];
  }
}
