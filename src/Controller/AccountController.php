<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\AdminBundle\Controller;

use Austral\AdminBundle\Page\FormPage;
use Austral\AdminBundle\Page\ListConfigurator;
use Austral\AdminBundle\Routing\AdminRouteLoader;
use Austral\AdminBundle\Routing\AdminUrlGenerator;
use Austral\AdminBundle\View\AdminRenderer;
use Austral\FormBundle\Field\PasswordField;
use Austral\FormBundle\Field\SelectField;
use Austral\FormBundle\Field\TextField;
use Austral\FormBundle\Form\Type\AustralFormType;
use Austral\FormBundle\Handler\FormHandler;
use Austral\FormBundle\Handler\FormResult;
use Austral\FormBundle\Mapper\FormMapper;
use Austral\FormBundle\Mapper\FormMapperFactory;
use Austral\SecurityBundle\Entity\Interfaces\UserInterface;
use Austral\SecurityBundle\EntityManager\Interfaces\UserEntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Austral Account Controller.
 *
 * "My account": the user changes his name, email, username, language, theme and password, his current password
 * required. The type, the groups and the state of the account are not in this form.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class AccountController
{
  public const string TEMPLATE = '@AustralAdmin/security/account.html.twig';

  /**
   * @param TokenStorageInterface      $tokenStorage
   * @param UserEntityManagerInterface $users
   * @param FormMapperFactory          $mapperFactory
   * @param FormFactoryInterface       $formFactory
   * @param FormHandler                $formHandler
   * @param AdminUrlGenerator          $urls
   * @param AdminRenderer              $renderer
   * @param list<string>               $languages     Languages of the interface
   */
  public function __construct(
    private readonly TokenStorageInterface $tokenStorage,
    private readonly UserEntityManagerInterface $users,
    private readonly FormMapperFactory $mapperFactory,
    private readonly FormFactoryInterface $formFactory,
    private readonly FormHandler $formHandler,
    private readonly AdminUrlGenerator $urls,
    private readonly AdminRenderer $renderer,
    private readonly array $languages = ['en', 'fr'],
  ) {
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function edit(Request $request): Response
  {
    $user = $this->tokenStorage->getToken()?->getUser();
    if (!$user instanceof UserInterface)
    {
      throw new AccessDeniedException('My account needs an Austral user.');
    }

    $languages = [];
    foreach ($this->languages as $language)
    {
      $languages["choices.language.{$language}"] = $language;
    }
    $mapper = $this->mapperFactory->create('account', $user::class, $user, static function (FormMapper $mapper) use ($languages): void {
      $mapper->setTranslationDomain(ListConfigurator::TRANSLATION_DOMAIN);
      $mapper->addFieldset('account', 'fieldset.account')
        ->add(TextField::create('firstname'))
        ->add(TextField::create('lastname'))
        ->add(TextField::create('email', self::requiredText()))
        ->add(TextField::create('username', self::requiredText()))
        ->add(SelectField::create('language', $languages, ['required' => true]))
        ->add(SelectField::create('interfaceTheme', [
          'choices.interfaceTheme.auto' => UserInterface::USER_INTERFACE_AUTO,
          'choices.interfaceTheme.light' => UserInterface::USER_INTERFACE_LIGHT,
          'choices.interfaceTheme.dark' => UserInterface::USER_INTERFACE_DARK,
        ]));
      $mapper->addFieldset('password', 'fieldset.password')
        ->add(PasswordField::create('plainPassword', [
          'auto_constraints' => false,
          'constraints' => [new Regex(pattern: PasswordField::MATCH_DEFAULT, message: 'errors.plainPassword.regex')],
          'helper' => 'helpers.plainPassword.keep',
        ]))
        ->add(PasswordField::create('currentPassword', [
          'repeat' => false,
          'mapped' => false,
          'required' => true,
          'auto_constraints' => false,
          'constraints' => [new UserPassword(message: 'errors.currentPassword.invalid')],
        ]));
    });
    $form = $this->formFactory->createNamed($mapper->getName(), AustralFormType::class, $user, ['mapper' => $mapper]);
    $result = $this->formHandler->process($form, $request, function () use ($user): void {
      $this->users->updateUser($user);
    });

    if ($result->isSaved())
    {
      FormPage::flash($request, 'success', 'flash.saved');

      return new RedirectResponse($this->urls->route(AdminRouteLoader::ROUTE_ACCOUNT));
    }
    $view = $form->createView();
    if (FormResult::STATUS_INVALID === $result->getStatus())
    {
      FormPage::flash($request, 'error', 'flash.invalid');
      // The user of the session must not keep the values refused by the form.
      $this->users->refresh($user);
    }

    return $this->renderer->render($request, 'account', self::TEMPLATE, [
      'form' => $view,
      'user' => $user,
    ], null, FormResult::STATUS_INVALID === $result->getStatus() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
  }

  /**
   * Options of a required text: never null (empty_data) and not blank.
   *
   * @return array<string, mixed>
   */
  private static function requiredText(): array
  {
    return ['required' => true, 'constraints' => [new NotBlank()], 'form_options' => ['empty_data' => '']];
  }
}
