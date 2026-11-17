<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Admin\Event;

use Austral\AdminBundle\Handler\AdminHandler;
use Austral\Contracts\Entity\EntityInterface;

/**
 * Austral Admin Event HttpCacheClear.
 * @template T of object
 *
 * @extends AdminEvent<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class HttpCacheClearAdminEvent extends AdminEvent
{

  /** @deprecated  */
  CONST string EVENT_START = "austral.admin.event.http_cache.clear.start";

  /** @deprecated  */
  CONST string EVENT_END = "austral.admin.event.http_cache.clear.end";

  /**
   * @var string|null
   */
  private ?string $uri = null;

  /**
   * @var bool
   */
  private bool $enabled = true;

  /**
   * FormAdminEvent constructor.
   *
   * @param AdminHandler<T> $adminHandler
   * @param EntityInterface<T>|null $object
   */
  public function __construct(AdminHandler $adminHandler, ?EntityInterface $object = null)
  {
    parent::__construct($adminHandler);
    $this->object = $object;
  }

  /**
   * getUri
   *
   * @return string|null
   */
  public function getUri(): ?string
  {
    return $this->uri;
  }

  /**
   * setUri
   *
   * @param string|null $uri
   * @return $this
   */
  public function setUri(?string $uri): self
  {
    $this->uri = $uri;
    return $this;
  }

  /**
   * isEnabled
   *
   * @return bool
   */
  public function getEnabled(): bool
  {
    return $this->enabled;
  }

  /**
   * setEnabled
   *
   * @param bool $enabled
   * @return $this
   */
  public function setEnabled(bool $enabled): self
  {
    $this->enabled = $enabled;
    return $this;
  }

}