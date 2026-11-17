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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral Deployment.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class Deployment
{

  /**
   * @var string
   */
  protected string $deploymentFilePath;

  /**
   * @param TemplateParameters<T> $templateParameters
   * @param TranslatorInterface $translator
   * @param string $projectPath
   * @param Push|null $push
   */
  public function __construct(
    #[Autowire(service: "austral.admin.template")] protected TemplateParameters $templateParameters,
    #[Autowire(service: "translator")] protected TranslatorInterface $translator,
    string $projectPath,
    #[Autowire(service: "austral.notify.push")] protected ?Push $push = null)
  {
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
    $message = null;
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
      $this->push->add(Push::TYPE_MERCURE, array('topics'=>array(
        "authenticated"
      ), "values" => array(
        'type'            =>  $status,
        "message"         =>  $this->translator->trans($message, array(), 'austral')
      )))->push(true, false);

    }
    return $status;
  }

}