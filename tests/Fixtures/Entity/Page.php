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

namespace Austral\AdminBundle\Tests\Fixtures\Entity;

use Austral\EntityBundle\Entity\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Entity of the test modules "pages" and "articles".
 *
 * @extends Entity<object>
 */
#[ORM\Table(name: 'test_page')]
#[ORM\Entity(repositoryClass: PageRepository::class)]
class Page extends Entity implements \Stringable
{
  public const string STATUS_DRAFT = 'draft';
  public const string STATUS_ONLINE = 'online';

  #[ORM\Id]
  #[ORM\Column(type: Types::STRING, length: 40)]
  protected string $id;

  #[ORM\Column(type: Types::STRING, length: 255)]
  protected string $name = '';

  #[ORM\Column(type: Types::STRING, length: 64)]
  protected string $category = 'news';

  #[ORM\Column(type: Types::BOOLEAN)]
  protected bool $isPublished = false;

  #[ORM\Column(type: Types::STRING, length: 16)]
  protected string $status = self::STATUS_DRAFT;

  #[ORM\Column(type: Types::INTEGER)]
  protected int $position = 0;

  public function __construct(string $name = '', string $category = 'news', int $position = 0)
  {
    parent::__construct();
    $this->id = Uuid::v4()->toRfc4122();
    $this->name = $name;
    $this->category = $category;
    $this->position = $position;
  }

  public function __toString(): string
  {
    return $this->name;
  }

  public function getId(): string
  {
    return $this->id;
  }

  public function setId(string $id): static
  {
    $this->id = $id;

    return $this;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function setName(?string $name): static
  {
    $this->name = (string) $name;

    return $this;
  }

  public function getCategory(): string
  {
    return $this->category;
  }

  public function setCategory(?string $category): static
  {
    $this->category = (string) $category;

    return $this;
  }

  public function getIsPublished(): bool
  {
    return $this->isPublished;
  }

  public function setIsPublished(bool $isPublished): static
  {
    $this->isPublished = $isPublished;

    return $this;
  }

  public function getStatus(): string
  {
    return $this->status;
  }

  public function setStatus(string $status): static
  {
    $this->status = $status;

    return $this;
  }

  public function getPosition(): int
  {
    return $this->position;
  }

  public function setPosition(int $position): static
  {
    $this->position = $position;

    return $this;
  }
}
