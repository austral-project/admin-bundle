<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Configuration;

/**
 * Austral Configuration Check.
 *
 * A line of the configuration page: a status and its explanation (translation keys, domain austral).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ConfigurationCheck
{
  public const string STATUS_SUCCESS = 'success';
  public const string STATUS_WARNING = 'warning';
  public const string STATUS_ERROR = 'error';
  public const string STATUS_INFO = 'info';

  /**
   * @param string      $category    Group of the check (security, environment...)
   * @param string      $key
   * @param string      $status      One of the STATUS_ constants
   * @param string      $label       Translation key
   * @param string|null $description Translation key
   * @param string|null $value       Displayed as it is
   */
  public function __construct(
    public readonly string $category,
    public readonly string $key,
    public readonly string $status,
    public readonly string $label,
    public readonly ?string $description = null,
    public readonly ?string $value = null,
  ) {
    if (!\in_array($status, [self::STATUS_SUCCESS, self::STATUS_WARNING, self::STATUS_ERROR, self::STATUS_INFO], true))
    {
      throw new \InvalidArgumentException(\sprintf('Unknown status "%s" for the configuration check "%s".', $status, $key));
    }
  }

  /**
   * @param string      $category
   * @param string      $key
   * @param bool        $success
   * @param string      $label
   * @param string|null $description
   * @param string      $failure     Status when the check fails
   *
   * @return self
   */
  public static function check(string $category, string $key, bool $success, string $label, ?string $description = null, string $failure = self::STATUS_WARNING): self
  {
    return new self($category, $key, $success ? self::STATUS_SUCCESS : $failure, $label, $description);
  }
}
