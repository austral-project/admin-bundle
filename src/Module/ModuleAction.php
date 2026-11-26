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

/**
 * Austral Module Action.
 *
 * Names of the actions of a module. "list" is the access to the module: the list of an entity module, the index
 * of a page module, the tiles of a container module.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleAction
{
  public const string LIST = 'list';
  public const string CREATE = 'create';
  public const string EDIT = 'edit';
  public const string DUPLICATE = 'duplicate';
  public const string DELETE = 'delete';
  public const string TRUNCATE = 'truncate';
  public const string TOGGLE = 'toggle';
  public const string SORT = 'sort';
  public const string EXPORT = 'export';

  /**
   * Actions of an entity module.
   */
  public const array ENTITY = [
    self::LIST,
    self::CREATE,
    self::EDIT,
    self::DUPLICATE,
    self::DELETE,
    self::TRUNCATE,
    self::TOGGLE,
    self::SORT,
    self::EXPORT,
  ];

  /**
   * Actions whose URL can end with the language of the content.
   */
  public const array WITH_LANGUAGE = [
    self::LIST,
    self::CREATE,
    self::EDIT,
    self::DUPLICATE,
    self::DELETE,
  ];

  /**
   * Names of the 3.1 accepted in "disabledActions".
   */
  public const array ALIASES = [
    'change' => self::TOGGLE,
    'download' => self::EXPORT,
    'sortable' => self::SORT,
  ];

  /**
   * Suffix of the role of an action (the role of the module followed by "_<suffix>"), null for the role of the
   * module. The suffixes of the 3.1 are kept: the roles saved in the database stay valid.
   */
  public const array ROLE_SUFFIXES = [
    self::LIST => null,
    self::CREATE => 'CREATE',
    self::EDIT => 'EDIT',
    self::DUPLICATE => 'DUPLICATE',
    self::DELETE => 'DELETE',
    self::TRUNCATE => 'TRUNCATE',
    self::TOGGLE => 'CHANGE',
    self::SORT => 'EDIT',
    self::EXPORT => 'DOWNLOAD',
  ];

  /**
   * @param string $action
   *
   * @return string The name of the action, the 3.1 names replaced
   */
  public static function normalize(string $action): string
  {
    $action = strtolower($action);

    return self::ALIASES[$action] ?? $action;
  }
}
