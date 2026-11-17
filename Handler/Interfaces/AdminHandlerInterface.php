<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\AdminBundle\Handler\Interfaces;

use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;
use Austral\Contracts\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Austral Admin Handler Interface.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminHandlerInterface
{

  /**
   * @param Module<T> $module
   *
   * @return $this
   */
  public function setModule(Module $module): self;

  /**
   * @return Module<T>|null
   */
  public function getModule(): ?Module;

  /**
   * @param Modules<T> $modules
   *
   * @return $this
   */
  public function setModules(Modules $modules): self;

  /**
   * @return Modules<T>|null
   */
  public function getModules(): ?Modules;

  /**
   * @return $this
   */
  public function index(): self;

  /**
   * @return $this
   */
  public function list(): self;

  /**
   * @param string $id
   *
   * @return $this
   */
  public function form(string $id): self;

  /**
   * @param string $id
   *
   * @return $this
   */
  public function duplicate(string $id): self;

  /**
   * @param string $format
   *
   * @return StreamedResponse
   */
  public function download(string $format): StreamedResponse;

  /**
   * @param string $id
   *
   * @return $this
   */
  public function delete(string $id): self;

  /**
   * @return $this
   */
  public function truncate(): self;

  /**
   * httpCacheClear
   *
   * @param EntityInterface<T>|null $object
   * @return $this
   */
  public function httpCacheClear(?EntityInterface $object = null): self;

}