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

use Austral\ToolsBundle\Configuration\BaseConfiguration;

/**
 * Austral Admin Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
Class AdminConfiguration extends BaseConfiguration
{

  /**
   * @var string|null
   */
  protected ?string $prefix = "admin";

  /**
   * @var int|null
   */
  protected ?int $niveauMax = null;

}