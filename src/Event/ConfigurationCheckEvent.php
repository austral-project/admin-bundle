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

use Austral\AdminBundle\Configuration\ConfigurationCheck;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Configuration Check Event.
 *
 * Checks of the configuration page (AdminEvents::CONFIGURATION_CHECK): each bundle adds its own.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ConfigurationCheckEvent extends Event
{
  /**
   * @var array<string, ConfigurationCheck>
   */
  private array $checks = [];

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
   * @param ConfigurationCheck $check Replaces the check of the same category and key
   *
   * @return self
   */
  public function add(ConfigurationCheck $check): self
  {
    $this->checks["{$check->category}.{$check->key}"] = $check;

    return $this;
  }

  /**
   * @return list<ConfigurationCheck>
   */
  public function getChecks(): array
  {
    return array_values($this->checks);
  }

  /**
   * @return array<string, list<ConfigurationCheck>> By category
   */
  public function getChecksByCategory(): array
  {
    $categories = [];
    foreach ($this->checks as $check)
    {
      $categories[$check->category][] = $check;
    }

    return $categories;
  }
}
