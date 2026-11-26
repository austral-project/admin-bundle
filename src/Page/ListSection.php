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

use Doctrine\ORM\QueryBuilder;

/**
 * Austral List Section.
 *
 * A part of the list of a module, with its own query, pagination and filter group (the group of the same name,
 * "default" otherwise): the homepage and the other pages.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ListSection
{
  public const string DEFAULT = 'default';

  /**
   * @param string                                      $name    Letters, digits and "_"
   * @param string|false|null                           $label   Translation key, false for none, null for the default one
   * @param (\Closure(QueryBuilder, string): void)|null $query   Restriction of the section (root alias given)
   * @param int|null                                    $perPage Defaults to the one of the list, 0 for no pagination
   */
  public function __construct(
    public readonly string $name,
    public readonly string|false|null $label = null,
    public readonly ?\Closure $query = null,
    public readonly ?int $perPage = null,
  ) {
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name))
    {
      throw new \InvalidArgumentException(\sprintf('The section name "%s" must start with a letter and contain only letters, digits and "_".', $name));
    }
  }

  /**
   * @return string Query parameter of the page number
   */
  public function getPageParameter(): string
  {
    return self::DEFAULT === $this->name ? 'page' : "{$this->name}_page";
  }
}
