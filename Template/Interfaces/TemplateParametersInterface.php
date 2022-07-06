<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\AdminBundle\Template\Interfaces;

use Austral\AdminBundle\Configuration\AdminConfiguration;
use Austral\AdminBundle\Module\Module;
use Austral\AdminBundle\Module\Modules;
use Austral\AdminBundle\Template\Elements\Breadcrumb;
use Austral\AdminBundle\Template\Elements\Navigation;

/**
 * Austral Admin Template Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface TemplateParametersInterface
{

  /**
   * TemplateParameters constructor.
   *
   * @param AdminConfiguration $adminConfiguration
   */
  public function __construct(AdminConfiguration $adminConfiguration);

  /**
   * @return Navigation
   */
  public function navigation(): Navigation;

  /**
   * @return Breadcrumb
   */
  public function breadcrumb(): Breadcrumb;

  /**
   * @return $this
   */
  public function initTemplate(): TemplateParametersInterface;

  /**
   * @param Modules $modules
   *
   * @return $this
   */
  public function setModules(Modules $modules): TemplateParametersInterface;

  /**
   * @param Module $module
   *
   * @return $this
   */
  public function setModule(Module $module): TemplateParametersInterface;

}