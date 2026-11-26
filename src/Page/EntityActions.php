<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Page;

use Austral\AdminBundle\Event\AdminEventDispatcher;
use Austral\AdminBundle\Event\AdminEvents;
use Austral\AdminBundle\Event\ObjectAdminEvent;
use Austral\AdminBundle\Event\QueryAdminEvent;
use Austral\AdminBundle\Event\SortAdminEvent;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\ModuleAction;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\Security\CsrfGuard;
use Austral\Contracts\Entity\EntityInterface;
use Austral\ListBundle\Column\ChoicesColumn;
use Austral\ListBundle\Column\Column;
use Austral\ListBundle\Column\SwitchColumn;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Austral Entity Actions.
 *
 * The actions of an entity module that change data: delete, duplicate, truncate, toggle and sort. They are POST
 * requests with the CSRF token of the admin, and read the entities through EntityQuery (the restrictions of the
 * module apply).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class EntityActions
{
  /**
   * Entities deleted by query of the truncate.
   */
  public const int TRUNCATE_BATCH = 100;

  private readonly PropertyAccessorInterface $propertyAccessor;

  /**
   * @param EntityQuery                    $query
   * @param ListConfigurator               $configurator
   * @param AdminEventDispatcher           $dispatcher
   * @param CsrfGuard                      $csrf
   * @param AdminUrlGenerator              $urls
   * @param ListUrlStore                   $listUrls
   * @param PropertyAccessorInterface|null $propertyAccessor
   */
  public function __construct(
    private readonly EntityQuery $query,
    private readonly ListConfigurator $configurator,
    private readonly AdminEventDispatcher $dispatcher,
    private readonly CsrfGuard $csrf,
    private readonly AdminUrlGenerator $urls,
    private readonly ListUrlStore $listUrls,
    ?PropertyAccessorInterface $propertyAccessor = null,
  ) {
    $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
  }

  /**
   * @param Module  $module
   * @param Request $request
   * @param string  $id
   *
   * @return Response
   */
  public function delete(Module $module, Request $request, string $id): Response
  {
    $this->csrf->check($request);
    $object = $this->query->find($module, $request, $id);
    $this->dispatcher->dispatch(new ObjectAdminEvent($module, $request, $object), AdminEvents::DELETE_BEFORE);
    $entityManager = $module->getEntityManager();
    if ($object instanceof EntityInterface)
    {
      $entityManager->delete($object);
    }
    else
    {
      $entityManager->getDoctrineEntityManager()->remove($object);
      $entityManager->flush();
    }
    $this->dispatcher->dispatch(new ObjectAdminEvent($module, $request, $object), AdminEvents::DELETE_AFTER);
    FormPage::flash($request, 'success', 'flash.deleted');

    return $this->back($module, $request, ['status' => 'deleted', 'id' => $id]);
  }

  /**
   * @param Module  $module
   * @param Request $request
   * @param string  $id
   *
   * @return Response
   */
  public function duplicate(Module $module, Request $request, string $id): Response
  {
    $this->csrf->check($request);
    $source = $this->query->find($module, $request, $id);
    if (!$source instanceof EntityInterface)
    {
      throw new BadRequestHttpException(\sprintf('The entities of the admin module "%s" cannot be duplicated.', $module->getPath()));
    }
    $entityManager = $module->getEntityManager();
    $copy = $entityManager->duplicate($source);
    $this->dispatcher->dispatch(new ObjectAdminEvent($module, $request, $copy, $source), AdminEvents::DUPLICATE);
    $entityManager->update($copy);
    FormPage::flash($request, 'success', 'flash.duplicated');

    if ($module->isGranted(ModuleAction::EDIT))
    {
      return new RedirectResponse($this->urls->object($module, ModuleAction::EDIT, $copy));
    }

    return $this->back($module, $request, ['status' => 'duplicated', 'id' => AdminUrlGenerator::id($copy)]);
  }

  /**
   * Deletes the entities of the module in batches, through the entity manager (its events, the cascades and the
   * restrictions of the module apply).
   *
   * @param Module  $module
   * @param Request $request
   *
   * @return Response
   */
  public function truncate(Module $module, Request $request): Response
  {
    $this->csrf->check($request);
    $entityManager = $module->getEntityManager();
    $identifier = $this->query->identifier($module);
    $deleted = [];
    while (true)
    {
      $queryBuilder = $this->query->queryBuilder($module, $request, QueryAdminEvent::CONTEXT_TRUNCATE)->setMaxResults(self::TRUNCATE_BATCH);
      $objects = array_filter($queryBuilder->getQuery()->getResult(), 'is_object');
      $objects = array_filter($objects, function (object $object) use ($identifier, &$deleted): bool {
        $id = (string) $this->propertyAccessor->getValue($object, $identifier);

        return !isset($deleted[$id]);
      });
      if (!$objects)
      {
        break;
      }
      foreach ($objects as $object)
      {
        $deleted[(string) $this->propertyAccessor->getValue($object, $identifier)] = true;
        $this->dispatcher->dispatch(new ObjectAdminEvent($module, $request, $object), AdminEvents::DELETE_BEFORE);
        if ($object instanceof EntityInterface)
        {
          $entityManager->delete($object, false);
        }
        else
        {
          $entityManager->getDoctrineEntityManager()->remove($object);
        }
      }
      $entityManager->flush();
      foreach ($objects as $object)
      {
        $this->dispatcher->dispatch(new ObjectAdminEvent($module, $request, $object), AdminEvents::DELETE_AFTER);
      }
    }
    FormPage::flash($request, 'success', 'flash.truncated');

    return $this->back($module, $request, ['status' => 'truncated', 'count' => \count($deleted)]);
  }

  /**
   * Changes the value of a SwitchColumn or a ChoicesColumn of the list (its "url" option set, see
   * ListAdminEvent::toggle()), to one of its values, when its "granted" option allows it for the entity.
   *
   * @param Module  $module
   * @param Request $request
   * @param string  $id
   * @param string  $field   Name of the column
   *
   * @return Response
   */
  public function toggle(Module $module, Request $request, string $id, string $field): Response
  {
    $this->csrf->check($request);
    $mapper = $this->configurator->configureList($module, $request)->getMapper();
    $column = $mapper->has($field) ? $mapper->get($field) : null;
    if ((!$column instanceof SwitchColumn && !$column instanceof ChoicesColumn) || null === $column->getOption('url'))
    {
      throw new BadRequestHttpException(\sprintf('The column "%s" of the admin module "%s" cannot be changed from the list.', $field, $module->getPath()));
    }
    $object = $this->query->find($module, $request, $id);
    $granted = $column->getOption('granted');
    if (!($granted instanceof \Closure ? (bool) $granted($object) : (bool) $granted))
    {
      throw new AccessDeniedException(\sprintf('The column "%s" of this entity cannot be changed.', $field));
    }

    $raw = $request->query->get('value', $request->request->get('value'));
    $allowed = self::allowedValues($column);
    if (!\is_scalar($raw) || !\array_key_exists((string) $raw, $allowed))
    {
      throw new BadRequestHttpException(\sprintf('The value is not a value of the column "%s".', $field));
    }
    $path = $column->getOption('property_path') ?? $field;
    if (!\is_string($path) || !$this->propertyAccessor->isWritable($object, $path))
    {
      throw new BadRequestHttpException(\sprintf('The property of the column "%s" cannot be written.', $field));
    }
    $value = self::convert($allowed[(string) $raw], $this->propertyAccessor->getValue($object, $path));
    $this->propertyAccessor->setValue($object, $path, $value);
    $this->dispatcher->dispatch(new ObjectAdminEvent($module, $request, $object, null, $field, $value), AdminEvents::TOGGLE);
    $this->save($module, $object);

    return $this->back($module, $request, ['status' => 'changed', 'id' => $id, 'field' => $field, 'value' => ChoicesColumn::choiceKey($value)]);
  }

  /**
   * New order of entities: the ids in the field "ids", the positions they had are given again in this order (the
   * other entities keep theirs).
   *
   * @param Module  $module
   * @param Request $request
   *
   * @return Response
   */
  public function sort(Module $module, Request $request): Response
  {
    $this->csrf->check($request);
    $field = $module->getDefinition()->getSortableField() ?? throw new BadRequestHttpException('The admin module is not sortable.');
    $ids = array_values(array_unique(array_filter(array_map(static fn (mixed $id): string => \is_scalar($id) ? (string) $id : '', $request->request->all('ids')), static fn (string $id): bool => '' !== $id)));
    $objects = $this->query->findByIds($module, $request, $ids, QueryAdminEvent::CONTEXT_SORT);
    $identifier = $this->query->identifier($module);
    $byId = [];
    foreach ($objects as $object)
    {
      $byId[(string) $this->propertyAccessor->getValue($object, $identifier)] = $object;
    }
    if (\count($byId) !== \count($ids))
    {
      throw new BadRequestHttpException('Some entities of the new order do not exist.');
    }
    $positions = array_map(fn (object $object): int => (int) $this->propertyAccessor->getValue($object, $field), array_values($byId));
    sort($positions);
    $ordered = [];
    foreach ($ids as $index => $id)
    {
      $this->propertyAccessor->setValue($byId[$id], $field, $positions[$index] ?? $index);
      $ordered[] = $byId[$id];
    }
    $this->dispatcher->dispatch(new SortAdminEvent($module, $request, $ordered, $field), AdminEvents::SORT);
    $entityManager = $module->getEntityManager();
    foreach ($ordered as $object)
    {
      if ($object instanceof EntityInterface)
      {
        $entityManager->update($object, false);
      }
    }
    $entityManager->flush();

    return $this->back($module, $request, ['status' => 'sorted', 'ids' => $ids]);
  }

  /**
   * @param Column $column
   *
   * @return array<string, mixed> Key of a value => value
   */
  private static function allowedValues(Column $column): array
  {
    if ($column instanceof SwitchColumn)
    {
      $values = [];
      foreach (['on_value', 'off_value'] as $option)
      {
        $value = $column->getOption($option);
        $values[(string) ChoicesColumn::choiceKey($value)] = $value;
      }

      return $values;
    }
    $choices = $column->getOption('choices');
    $values = [];
    foreach (\is_array($choices) ? array_keys($choices) : [] as $key)
    {
      $values[(string) $key] = (string) $key;
    }

    return $values;
  }

  /**
   * The value in the type of the current value (an enum case, an integer, a boolean).
   *
   * @param mixed $value
   * @param mixed $current
   *
   * @return mixed
   */
  private static function convert(mixed $value, mixed $current): mixed
  {
    $key = (string) ChoicesColumn::choiceKey($value);

    return match (true)
    {
      $current instanceof \BackedEnum => $current::from(\is_int($current->value) ? (int) $key : $key),
      \is_bool($current) => \in_array(strtolower($key), ['1', 'true', 'on', 'yes'], true),
      \is_int($current) => (int) $key,
      \is_float($current) => (float) $key,
      \is_string($current) => $key,
      default => $value,
    };
  }

  /**
   * @param Module $module
   * @param object $object
   *
   * @return void
   */
  private function save(Module $module, object $object): void
  {
    $entityManager = $module->getEntityManager();
    if ($object instanceof EntityInterface)
    {
      $entityManager->update($object);

      return;
    }
    $entityManager->flush();
  }

  /**
   * JSON for an asynchronous request, the last URL of the list otherwise.
   *
   * @param Module               $module
   * @param Request              $request
   * @param array<string, mixed> $data
   *
   * @return Response
   */
  private function back(Module $module, Request $request, array $data): Response
  {
    if ($request->isXmlHttpRequest() || 'json' === $request->getPreferredFormat())
    {
      return new JsonResponse($data);
    }

    return new RedirectResponse($this->listUrls->get($module, $request));
  }
}
