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
 * Austral Variant Not Found Exception.
 *
 * A variant key of the URL is not given by the provider of its level (404).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class VariantNotFoundException extends \InvalidArgumentException
{
  /**
   * @param string $path
   * @param int    $level
   * @param string $key
   */
  public function __construct(public readonly string $path, public readonly int $level, public readonly string $key)
  {
    parent::__construct(\sprintf('The variant "%s" (level %d) of the admin module "%s" does not exist.', $key, $level, $path));
  }
}
