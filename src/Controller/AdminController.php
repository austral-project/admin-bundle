<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Austral Admin Controller.
 *
 * Dashboard, my account and the actions of the modules (routes of AdminRouteLoader). Each action calls the method
 * of the same name of the AdminHandler, then returns its response or renders its template parameters. The module
 * and its rights are checked before, by HttpAdminEventSubscriber.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AdminController extends AbstractAdminController
{
  /**
   * index
   * Dashboard of the admin.
   *
   * @return Response
   */
  public function index(): Response
  {
    $this->handler()->dashboard();

    return $this->respond();
  }

  /**
   * account
   * My account.
   *
   * @return Response
   */
  public function account(): Response
  {
    $this->handler()->account();

    return $this->respond();
  }

  /**
   * list
   *
   * @return Response
   */
  public function list(): Response
  {
    $this->handler()->list();

    return $this->respond();
  }

  /**
   * variants
   *
   * @return Response
   */
  public function variants(): Response
  {
    $this->handler()->variants();

    return $this->respond();
  }

  /**
   * container
   *
   * @return Response
   */
  public function container(): Response
  {
    $this->handler()->container();

    return $this->respond();
  }

  /**
   * create
   *
   * @return Response
   */
  public function create(): Response
  {
    $this->handler()->create();

    return $this->respond();
  }

  /**
   * edit
   *
   * @return Response
   */
  public function edit(): Response
  {
    $this->handler()->edit();

    return $this->respond();
  }

  /**
   * duplicate
   *
   * @return Response
   */
  public function duplicate(): Response
  {
    $this->handler()->duplicate();

    return $this->respond();
  }

  /**
   * delete
   *
   * @return Response
   */
  public function delete(): Response
  {
    $this->handler()->delete();

    return $this->respond();
  }

  /**
   * truncate
   *
   * @return Response
   */
  public function truncate(): Response
  {
    $this->handler()->truncate();

    return $this->respond();
  }

  /**
   * toggle
   *
   * @return Response
   */
  public function toggle(): Response
  {
    $this->handler()->toggle();

    return $this->respond();
  }

  /**
   * sort
   *
   * @return Response
   */
  public function sort(): Response
  {
    $this->handler()->sort();

    return $this->respond();
  }

  /**
   * export
   *
   * @return Response
   */
  public function export(): Response
  {
    $this->handler()->export();

    return $this->respond();
  }

  /**
   * action
   *
   * @return Response
   */
  public function action(): Response
  {
    $this->handler()->action();

    return $this->respond();
  }

  /**
   * page
   *
   * @return Response
   */
  public function page(): Response
  {
    $this->handler()->page();

    return $this->respond();
  }
}
