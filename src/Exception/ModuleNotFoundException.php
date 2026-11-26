<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Exception;

/**
 * Austral Module Not Found Exception.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ModuleNotFoundException extends \InvalidArgumentException
{
  /**
   * @param string $path
   */
  public function __construct(public readonly string $path)
  {
    parent::__construct(\sprintf('The admin module "%s" does not exist.', $path));
  }
}
