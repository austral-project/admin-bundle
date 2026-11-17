<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\AdminBundle\Admin;

use Austral\AdminBundle\Admin\Event\AdminEventInterface;
use Austral\AdminBundle\Module\Module;
use Austral\Contracts\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Admin Interface.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface AdminInterface
{

  /**
   * @param Module<T> $module
   *
   * @return $this
   */
  public function setModule(Module $module): self;

  /**
   * @param string $currentLanguage
   *
   * @return $this
   */
  public function setCurrentLanguage(string $currentLanguage): self;

  /**
   * @param ContainerInterface $container
   *
   * @return $this
   */
  public function setContainer(ContainerInterface $container): self;

  /**
   * @param TranslatorInterface $translator
   *
   * @return $this
   */
  public function setTranslator(TranslatorInterface $translator): self;

  /**
   * @return array<string, array<int, list<string>>>
   */
  public function getEvents(): array;

  /**
   * @param string $eventName
   * @param AdminEventInterface<T> $managerEvent
   *
   * @return void
   */
  public function dispatch(string $eventName, AdminEventInterface $managerEvent): void;

  /**
   * @param EntityInterface<T> $object
   *
   * @return $this
   */
  public function setObject(EntityInterface $object): self;

  /**
   * @return bool
   */
  public function isDevEnv(): bool;

}