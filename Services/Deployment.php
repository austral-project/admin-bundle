<?php
/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Services;

use Austral\AdminBundle\Template\TemplateParameters;
use Austral\NotifyBundle\Notification\Push;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Deployment.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class Deployment
{
  /**
   * @var TemplateParameters
   */
  protected TemplateParameters $templateParameters;

  /**
   * @var TranslatorInterface
   */
  protected TranslatorInterface $translator;

  /**
   * @var string
   */
  protected string $deploymentFilePath;

  /**
   * @var Push|null
   */
  protected ?Push $push;

  /**
   * @param TemplateParameters $templateParameters
   * @param TranslatorInterface $translator
   * @param string $projectPath
   * @param Push|null $push
   */
  public function __construct(TemplateParameters $templateParameters, TranslatorInterface $translator, string $projectPath, ?Push $push = null)
  {
    $this->templateParameters = $templateParameters;
    $this->translator = $translator;
    $this->push = $push;
    $this->deploymentFilePath = "{$projectPath}/deployment.pid";
  }

  /**
   * isStarted
   * @return bool
   */
  public function isStarted(): bool
  {
    if(file_exists($this->deploymentFilePath))
    {
      $this->templateParameters->addParameters("deployment", "start");
      return true;
    }
    return false;
  }

  /**
   * execute
   * @param string|null $forceStatus
   * @return string
   * @throws \Exception
   */
  public function execute(?string $forceStatus = null): string
  {
    $status = "no-push";
    if($this->push)
    {
      $deploymentStatus = null;
      $isStarted = $this->isStarted();
      if(!$isStarted || $forceStatus === "start")
      {
        $deploymentStatus = "start";
      }
      if($forceStatus === "stop" || !$deploymentStatus)
      {
        $deploymentStatus = "stop";
      }

      if($deploymentStatus === "start")
      {
        file_put_contents($this->deploymentFilePath, "started");
        $status = "deployment-start";
        $message = "austral.deployment.start";
      }
      elseif($deploymentStatus === "stop")
      {
        if($isStarted) {
          unlink($this->deploymentFilePath);
        }
        $status = "deployment-stop";
        $message = "austral.deployment.stop";
      }

      if($deploymentStatus)
      {
        $this->push->add(Push::TYPE_MERCURE, array('topics'=>array(
          "authenticated"
        ), "values" => array(
          'type'            =>  $status,
          "message"         =>  $this->translator->trans($message, array(), 'austral')
        )))->push(true, false);
      }

    }
    return $status;
  }

}