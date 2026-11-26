<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Event;

use Austral\AdminBundle\Module\Module;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Query Admin Event.
 *
 * A query of an entity module, root alias "root". The restrictions added here (a user sees only some entities)
 * apply to the list, to the loading of the entity of an action (edit, delete, toggle...), to the export and to
 * the truncate: an entity outside the restrictions is a 404.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class QueryAdminEvent extends AdminEvent
{
  public const string CONTEXT_LIST = 'list';
  public const string CONTEXT_OBJECT = 'object';
  public const string CONTEXT_EXPORT = 'export';
  public const string CONTEXT_TRUNCATE = 'truncate';
  public const string CONTEXT_SORT = 'sort';

  /**
   * @param Module       $module
   * @param Request      $request
   * @param QueryBuilder $queryBuilder
   * @param string       $context      One of the CONTEXT_ constants
   * @param string|null  $section      Section of the list (CONTEXT_LIST)
   */
  public function __construct(
    Module $module,
    Request $request,
    private readonly QueryBuilder $queryBuilder,
    private readonly string $context,
    private readonly ?string $section = null,
  ) {
    parent::__construct($module, $request);
  }

  /**
   * @return QueryBuilder
   */
  public function getQueryBuilder(): QueryBuilder
  {
    return $this->queryBuilder;
  }

  /**
   * @return string
   */
  public function getContext(): string
  {
    return $this->context;
  }

  /**
   * @param string $context
   *
   * @return bool
   */
  public function is(string $context): bool
  {
    return $this->context === $context;
  }

  /**
   * @return string|null
   */
  public function getSection(): ?string
  {
    return $this->section;
  }

  /**
   * @return string
   */
  public function getRootAlias(): string
  {
    return $this->queryBuilder->getRootAliases()[0] ?? 'root';
  }
}
