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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Dashboard Event.
 *
 * Blocks of the dashboard (AdminEvents::DASHBOARD). A block is a template and its variables; the admin adds the
 * tiles of the modules ("modules", position 0).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class DashboardEvent extends Event
{
  /**
   * @var array<string, array{name: string, template: string, vars: array<string, mixed>, position: int}>
   */
  private array $blocks = [];

  /**
   * @param Request $request
   */
  public function __construct(private readonly Request $request)
  {
  }

  /**
   * @return Request
   */
  public function getRequest(): Request
  {
    return $this->request;
  }

  /**
   * @param string               $name
   * @param string               $template
   * @param array<string, mixed> $vars
   * @param int                  $position The smallest first
   *
   * @return self
   */
  public function addBlock(string $name, string $template, array $vars = [], int $position = 100): self
  {
    $this->blocks[$name] = ['name' => $name, 'template' => $template, 'vars' => $vars, 'position' => $position];

    return $this;
  }

  /**
   * @param string $name
   *
   * @return self
   */
  public function removeBlock(string $name): self
  {
    unset($this->blocks[$name]);

    return $this;
  }

  /**
   * @param string $name
   *
   * @return bool
   */
  public function hasBlock(string $name): bool
  {
    return isset($this->blocks[$name]);
  }

  /**
   * @return list<array{name: string, template: string, vars: array<string, mixed>, position: int}> By position
   */
  public function getBlocks(): array
  {
    $blocks = array_values($this->blocks);
    $order = array_flip(array_keys($this->blocks));
    usort($blocks, static fn (array $a, array $b): int => [$a['position'], $order[$a['name']]] <=> [$b['position'], $order[$b['name']]]);

    return $blocks;
  }
}
