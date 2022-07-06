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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Austral Admin Handler Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminHandlerInterface
{

  /**
   * @param Module $module
   *
   * @return $this
   */
  public function setModule(Module $module): AdminHandlerInterface;

  /**
   * @return Module
   */
  public function getModule(): Module;

  /**
   * @return $this
   */
  public function index(): AdminHandlerInterface;

  /**
   * @return $this
   */
  public function list(): AdminHandlerInterface;

  /**
   * @param string $id
   *
   * @return $this
   */
  public function form(string $id): AdminHandlerInterface;

  /**
   * @param string $id
   *
   * @return $this
   */
  public function duplicate(string $id): AdminHandlerInterface;

  /**
   * @var string $format
   *
   * @return StreamedResponse
   */
  public function download(string $format): StreamedResponse;

  /**
   * @param string $id
   *
   * @return $this
   */
  public function delete(string $id): AdminHandlerInterface;

  /**
   * @return $this
   */
  public function truncate(): AdminHandlerInterface;

}