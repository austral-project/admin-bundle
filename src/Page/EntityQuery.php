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
use Austral\AdminBundle\Event\QueryAdminEvent;
use Austral\AdminBundle\Module\Module;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Austral Entity Query.
 *
 * Queries of an entity module, root alias "root": the restrictions of the variants, then of the query event
 * (Admin::configureQuery() and the listeners), then of the section. Every entity read by an action goes
 * through here: an entity outside the restrictions is not found.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class EntityQuery
{
  public const string ROOT_ALIAS = 'root';

  /**
   * @param AdminEventDispatcher $dispatcher
   */
  public function __construct(private readonly AdminEventDispatcher $dispatcher)
  {
  }

  /**
   * @param Module           $module
   * @param Request          $request
   * @param string           $context One of the QueryAdminEvent::CONTEXT_ constants
   * @param ListSection|null $section
   *
   * @return QueryBuilder
   */
  public function queryBuilder(Module $module, Request $request, string $context, ?ListSection $section = null): QueryBuilder
  {
    $queryBuilder = $module->getEntityManager()->createQueryBuilder(self::ROOT_ALIAS)
      ?? throw new \LogicException(\sprintf('The entity manager of the admin module "%s" has no repository.', $module->getPath()));
    foreach ($module->getVariants() as $variant)
    {
      if (null !== $variant->query)
      {
        ($variant->query)($queryBuilder, self::ROOT_ALIAS);
      }
    }
    $this->dispatcher->dispatch(new QueryAdminEvent($module, $request, $queryBuilder, $context, $section?->name), AdminEvents::QUERY);
    if (null !== $section?->query)
    {
      ($section->query)($queryBuilder, self::ROOT_ALIAS);
    }

    return $queryBuilder;
  }

  /**
   * @param Module  $module
   * @param Request $request
   * @param string  $id
   *
   * @return object
   *
   * @throws NotFoundHttpException
   */
  public function find(Module $module, Request $request, string $id): object
  {
    $objects = $this->findByIds($module, $request, [$id], QueryAdminEvent::CONTEXT_OBJECT);

    return $objects[0] ?? throw new NotFoundHttpException(\sprintf('The entity "%s" of the admin module "%s" does not exist.', $id, $module->getPath()));
  }

  /**
   * @param Module       $module
   * @param Request      $request
   * @param list<string> $ids
   * @param string       $context
   *
   * @return list<object> In the order of the query, the unknown ids left out
   */
  public function findByIds(Module $module, Request $request, array $ids, string $context): array
  {
    if (!$ids)
    {
      return [];
    }
    $queryBuilder = $this->queryBuilder($module, $request, $context);
    $identifier = $this->identifier($module);
    $queryBuilder->andWhere($queryBuilder->expr()->in(self::ROOT_ALIAS.".{$identifier}", ':austral_admin_ids'))
      ->setParameter('austral_admin_ids', $ids);

    /* @var list<object> */
    return array_values(array_filter($queryBuilder->getQuery()->getResult(), 'is_object'));
  }

  /**
   * @param Module $module
   *
   * @return string Identifier field of the entity
   */
  public function identifier(Module $module): string
  {
    $metadata = $module->getEntityManager()->getClassMetadata()
      ?? throw new \LogicException(\sprintf('The entity manager of the admin module "%s" has no entity class.', $module->getPath()));

    return $metadata->getSingleIdentifierFieldName();
  }
}
