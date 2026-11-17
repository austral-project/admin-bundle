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

use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\NotifyBundle\Mercure\Mercure;
use Austral\NotifyBundle\Notification\Push;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\SecurityBundle\EntityManager\UserEntityManager;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\QueryException;
use Twig\Environment;

/**
 * Austral Conflict Detect.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class ConflictDetect
{

  /**
   * @var Mercure|null
   */
  protected ?Mercure $mercure;

  /**
   * @var Push|null
   */
  protected ?Push $push;

  /**
   * @var Environment
   */
  protected Environment $twig;

  /**
   * @var Generator<T>
   */
  protected Generator $linkGenerator;

  /**
   * @var array<string, bool>
   */
  protected array $topicsConflict;
  
  /**
   * @var Collection<int, UserInterface<T>>
   */
  protected Collection $users;

  /**
   * @param UserEntityManager<T> $userEntityManager
   * @param Environment $twig
   * @param Generator<T> $linkGenerator
   * @param Mercure|null $mercure
   * @param Push|null $push
   */
  public function __construct(UserEntityManager $userEntityManager, Environment $twig, Generator $linkGenerator, ?Mercure $mercure = null, ?Push $push = null)
  {
    $this->mercure = $mercure;
    $this->push = $push;
    $this->users = new ArrayCollection();
    try {
      /** @var Collection<int, UserInterface<T>> $users */
      $users = $userEntityManager->selectAll("id", "ASC", function(AustralQueryBuilder $queryBuilder) {
        $queryBuilder->indexBy("root", "root.id")
          ->where("root.typeUser != :userType")
          ->setParameter("userType", "user");
      });
      $this->users = $users;
    } catch (QueryException $e) {

    }
    $this->twig = $twig;
    $this->linkGenerator = $linkGenerator;
    $this->topicsConflict = array();
  }

  /**
   * execute
   *
   * @return void
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   * @throws \Exception
   */
  public function execute(): void
  {
    if($this->mercure && $this->push)
    {
      foreach($this->mercure->listSubscribesByTopics() as $topic => $subscriptions)
      {
        if(!str_contains($topic, "/create/form"))
        {
          preg_match("^.*\/(.*)\/form(\/|)^", $topic, $matches);
          if($matches && (count($subscriptions) > 1))
          {
            $this->topicsConflict[$topic] = true;
            $users = array();
            foreach($subscriptions as $subscription)
            {
              if($userId = AustralTools::valueByKey(AustralTools::valueByKey($subscription, "payload", array()), "user-id", null))
              {
                /** @var UserInterface<T> $user */
                if($user = $this->users->get($userId))
                {
                  $users[] = array(
                    "firstname"     =>  $user->getFirstname(),
                    "lastname"      =>  $user->getLastname(),
                    "avatar"        =>  $this->linkGenerator->image($user, "avatar", "original", "resize", 100, 100),
                    "avatarColor"   =>  $user->getAvatarColor()
                  );
                }
              }
            }
            $template = $this->twig->render("@AustralDesign/Components/Notification/multi-user.html.twig", array(
              "users" =>  $users
            ));
            $this->push->add(Push::TYPE_MERCURE, array('topics'=>array($this->mercure->topicWithoutDomain($topic)), "values" => array(
              'type'            =>  "multi-user",
              "template"        =>  $template
            )))->push();
          }
          elseif(array_key_exists($topic, $this->topicsConflict))
          {
            unset($this->topicsConflict[$topic]);
            $this->push->add(Push::TYPE_MERCURE, array('topics'=>array($this->mercure->topicWithoutDomain($topic)), "values" => array(
              'type'            =>  "multi-user-finish",
            )))->push();
          }
        }
      }
    }

  }

}