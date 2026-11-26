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
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Page\ListSection;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\ListBundle\Mapper\ListMapper;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral List Admin Event.
 *
 * Configuration of the list of an entity module: the columns and actions of the list mapper, the sections and the
 * number of entities by page. The default actions (create, edit, duplicate, delete, export, truncate) are already
 * in the mapper: remove them with removeAction() / removeRowAction().
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ListAdminEvent extends AdminEvent
{
  /**
   * @var array<string, ListSection>
   */
  private array $sections = [];

  /**
   * @param Module            $module
   * @param Request           $request
   * @param ListMapper        $mapper
   * @param AdminUrlGenerator $urls
   * @param int               $perPage
   */
  public function __construct(
    Module $module,
    Request $request,
    private readonly ListMapper $mapper,
    private readonly AdminUrlGenerator $urls,
    private int $perPage,
  ) {
    parent::__construct($module, $request);
  }

  /**
   * @return ListMapper
   */
  public function getMapper(): ListMapper
  {
    return $this->mapper;
  }

  /**
   * @return ListMapper Alias of getMapper(), name of the 3.1
   */
  public function getListMapper(): ListMapper
  {
    return $this->mapper;
  }

  /**
   * Options of a SwitchColumn or a ChoicesColumn whose value can be changed from the list: "url" and "granted".
   * Only these columns can be changed by the toggle action, with their values only. Without the right (or with
   * the action disabled), "url" is null: the column is displayed only.
   *
   * @param string $field Name of the column
   *
   * @return array{url: (\Closure(object, mixed): string)|null, granted: \Closure(object|array<array-key, mixed>|null): bool}
   */
  public function toggle(string $field): array
  {
    $module = $this->module;
    $urls = $this->urls;
    $granted = $module->isGranted(ModuleAction::TOGGLE);

    return [
      'url' => $granted ? static fn (object $item, mixed $value): string => $urls->toggle($module, $item, $field, $value) : null,
      'granted' => static fn (object|array|null $item = null): bool => $granted,
    ];
  }

  /**
   * @param string                                      $name
   * @param string|false|null                           $label
   * @param (\Closure(QueryBuilder, string): void)|null $query
   * @param int|null                                    $perPage
   *
   * @return self
   */
  public function addSection(string $name, string|false|null $label = null, ?\Closure $query = null, ?int $perPage = null): self
  {
    $this->sections[$name] = new ListSection($name, $label, $query, $perPage);

    return $this;
  }

  /**
   * @param string $name
   *
   * @return self
   */
  public function removeSection(string $name): self
  {
    unset($this->sections[$name]);

    return $this;
  }

  /**
   * @return array<string, ListSection> The sections, a "default" section when none is added
   */
  public function getSections(): array
  {
    return $this->sections ?: [ListSection::DEFAULT => new ListSection(ListSection::DEFAULT, false)];
  }

  /**
   * @return int
   */
  public function getPerPage(): int
  {
    return $this->perPage;
  }

  /**
   * @param int $perPage 0 for no pagination
   *
   * @return self
   */
  public function setPerPage(int $perPage): self
  {
    $this->perPage = max(0, $perPage);

    return $this;
  }
}
