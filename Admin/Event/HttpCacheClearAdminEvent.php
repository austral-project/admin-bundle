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
use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral Admin Event HttpCacheClear.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class HttpCacheClearAdminEvent extends AdminEvent
{

  CONST EVENT_START = "austral.admin.event.http_cache.clear.start";
  CONST EVENT_END = "austral.admin.event.http_cache.clear.end";

  /**
   * @var EntityInterface|null
   */
  private ?EntityInterface $object = null;

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
   * @param AdminHandler $adminHandler
   * @param EntityInterface|null $object
   */
  public function __construct(AdminHandler $adminHandler, ?EntityInterface $object = null)
  {
    parent::__construct($adminHandler);
    $this->object = $object;
  }

  /**
   * getObject
   *
   * @return EntityInterface|null
   */
  public function getObject(): ?EntityInterface
  {
    return $this->object;
  }

  /**
   * setObject
   *
   * @param EntityInterface|null $object
   * @return $this
   */
  public function setObject(?EntityInterface $object): HttpCacheClearAdminEvent
  {
    $this->object = $object;
    return $this;
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
  public function setUri(?string $uri): HttpCacheClearAdminEvent
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
  public function setEnabled(bool $enabled): HttpCacheClearAdminEvent
  {
    $this->enabled = $enabled;
    return $this;
  }

}