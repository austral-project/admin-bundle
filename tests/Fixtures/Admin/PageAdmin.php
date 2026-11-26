<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\AdminBundle\Tests\Fixtures\Admin;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\FilterAdminEvent;
use Austral\AdminBundle\Event\FormAdminEvent;
use Austral\AdminBundle\Event\ListAdminEvent;
use Austral\AdminBundle\Event\ObjectAdminEvent;
use Austral\AdminBundle\Event\PageAdminEvent;
use Austral\AdminBundle\Event\QueryAdminEvent;
use Austral\AdminBundle\Tests\Fixtures\Entity\Page;
use Austral\FilterBundle\Filter\SearchFilter;
use Austral\FormBundle\Field\SwitchField;
use Austral\FormBundle\Field\TextField;
use Austral\ListBundle\Column\ChoicesColumn;
use Austral\ListBundle\Column\SwitchColumn;
use Austral\ListBundle\Column\ValueColumn;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Admin of the test modules: the pages of the category "secret" are outside the module (restriction).
 */
class PageAdmin extends Admin
{
  /**
   * @var list<string>
   */
  public array $calls = [];

  public function getEvents(): array
  {
    return [
      AdminEvents::DUPLICATE => 'duplicated',
      AdminEvents::FORM_SAVE_BEFORE => ['beforeSave', 10],
    ];
  }

  public function configureQuery(QueryAdminEvent $event): void
  {
    $event->getQueryBuilder()->andWhere($event->getRootAlias().'.category != :secret')->setParameter('secret', 'secret');
  }

  public function configureListMapper(ListAdminEvent $event): void
  {
    $event->getMapper()
      ->add(new ValueColumn('name'))
      ->add(new ValueColumn('category'))
      ->add(new SwitchColumn('isPublished', $event->toggle('isPublished')))
      ->add(new ChoicesColumn('status', ['choices' => [Page::STATUS_DRAFT => 'Draft', Page::STATUS_ONLINE => 'Online']] + $event->toggle('status')))
      ->add(new ChoicesColumn('display', ['property_path' => 'status', 'choices' => [Page::STATUS_DRAFT => 'Draft', Page::STATUS_ONLINE => 'Online']]));
    $event->setPerPage(2);
  }

  public function configureFilterMapper(FilterAdminEvent $event): void
  {
    $event->getMapper()->filter()->add(new SearchFilter('search', ['properties' => ['name']]));
  }

  public function configureFormMapper(FormAdminEvent $event): void
  {
    $event->getMapper()->addFieldset('general')
      ->add(TextField::create('name', ['required' => true]))
      ->add(TextField::create('category'))
      ->add(SwitchField::create('isPublished'));
  }

  protected function beforeSave(FormAdminEvent $event): void
  {
    $this->calls[] = 'beforeSave';
  }

  protected function duplicated(ObjectAdminEvent $event): void
  {
    $object = $event->getObject();
    if ($object instanceof Page)
    {
      $object->setName($object->getName().' (copy)');
    }
  }

  public function publish(PageAdminEvent $event): void
  {
    $page = $event->getObject();
    if ($page instanceof Page && $event->getRequest()->isMethod('POST'))
    {
      $page->setIsPublished(true);
      $event->getModule()->getEntityManager()->update($page);
      $event->setResponse(new RedirectResponse('/published'));
    }
  }
}
