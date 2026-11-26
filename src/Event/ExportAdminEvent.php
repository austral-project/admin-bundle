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

use Austral\AdminBundle\Module\Module;
use Austral\ListBundle\Mapper\ListMapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Austral Export Admin Event.
 *
 * Columns of an export: a mapper configured as the list (its visible columns are the columns of the file).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ExportAdminEvent extends AdminEvent
{
  /**
   * @param Module     $module
   * @param Request    $request
   * @param ListMapper $mapper
   * @param string     $format
   * @param string     $filename Without extension
   */
  public function __construct(Module $module, Request $request, private readonly ListMapper $mapper, private readonly string $format, private string $filename)
  {
    parent::__construct($module, $request);
  }

  /**
   * @return ListMapper
   */
  public function getMapper(): ListMapper
  {
    return $this->mapper;
  }

  /**
   * @return string csv, json or xlsx
   */
  public function getFormat(): string
  {
    return $this->format;
  }

  /**
   * @return string
   */
  public function getFilename(): string
  {
    return $this->filename;
  }

  /**
   * @param string $filename Without extension
   *
   * @return self
   */
  public function setFilename(string $filename): self
  {
    $this->filename = $filename;

    return $this;
  }
}
