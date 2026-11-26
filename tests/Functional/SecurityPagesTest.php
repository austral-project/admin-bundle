<?php

/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\AdminBundle\Tests\Functional;

use Austral\AdminBundle\Tests\Fixtures\MailCollector;

/**
 * Login, login link, password forgotten and reset, my account.
 */
final class SecurityPagesTest extends AdminWebTestCase
{
  public function testLoginWithTheForm(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/login');
    self::assertResponseIsSuccessful();
    self::assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
    self::assertCount(1, $crawler->filter('a[href="/austral-admin/login/link"]'));
    self::assertCount(1, $crawler->filter('a[href="/austral-admin/password/forgot"]'));

    $this->client->submit($crawler->filter('form')->form(['_username' => 'root', '_password' => 'wrong']));
    self::assertResponseRedirects('/austral-admin/login');
    $crawler = $this->client->followRedirect();
    self::assertCount(1, $crawler->filter('[role="alert"]'));

    $this->client->submit($crawler->filter('form')->form(['_username' => 'root', '_password' => 'secret']));
    self::assertResponseRedirects('/austral-admin/');
    $this->client->followRedirect();
    self::assertResponseIsSuccessful();

    $this->client->request('GET', '/austral-admin/login');
    self::assertResponseRedirects('/austral-admin/', 302, 'A logged in user goes to the index.');
  }

  public function testLoginLinkWithAConfirmationPage(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/login/link');
    self::assertResponseIsSuccessful();
    $this->client->submit($crawler->filter('form')->form(['login' => 'root@austral.dev']));
    self::assertResponseIsSuccessful();
    self::assertSelectorExists('[role="status"]');

    $urls = $this->collector()->loginLinkUrls;
    self::assertCount(1, $urls);
    $crawler = $this->client->request('GET', $urls[0]);
    self::assertResponseIsSuccessful();
    self::assertSame('no-referrer', $this->client->getResponse()->headers->get('Referrer-Policy'));
    self::assertNull($this->client->getRequest()->getSession()->get('_security_austral_admin'), 'Opening the link does not log in.');

    $this->client->submit($crawler->filter('form')->form());
    self::assertResponseRedirects('/austral-admin/');
    $this->client->followRedirect();
    self::assertResponseIsSuccessful();
  }

  public function testLoginLinkOfAnUnknownUserGivesTheSameAnswer(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/login/link');
    $this->client->submit($crawler->filter('form')->form(['login' => 'nobody@austral.dev']));
    self::assertResponseIsSuccessful();
    self::assertSelectorExists('[role="status"]');
    self::assertSame([], $this->collector()->loginLinkUrls);

    $this->client->request('POST', '/austral-admin/login/link', ['login' => 'root']);
    self::assertResponseStatusCodeSame(403, 'The form needs its CSRF token.');
  }

  public function testPasswordReset(): void
  {
    $crawler = $this->client->request('GET', '/austral-admin/password/forgot');
    $this->client->submit($crawler->filter('form')->form(['login' => 'admin']));
    self::assertResponseIsSuccessful();
    $urls = $this->collector()->passwordResetUrls;
    self::assertCount(1, $urls);
    self::assertStringStartsWith('http://localhost/austral-admin/password/reset/', $urls[0]);

    $crawler = $this->client->request('GET', $urls[0]);
    self::assertResponseIsSuccessful();
    self::assertSame('no-referrer', $this->client->getResponse()->headers->get('Referrer-Policy'));

    $this->client->submit($crawler->filter('form')->form(['password' => 'Abcdefg1', 'password_confirmation' => 'Abcdefg2']));
    self::assertResponseStatusCodeSame(422);
    $crawler = $this->client->submit($crawler->filter('form')->form(['password' => 'short', 'password_confirmation' => 'short']));
    self::assertResponseStatusCodeSame(422);

    $this->client->submit($crawler->filter('form')->form(['password' => 'Abcdefg1', 'password_confirmation' => 'Abcdefg1']));
    self::assertResponseRedirects('/austral-admin/login');

    $this->client->request('GET', $urls[0]);
    self::assertResponseStatusCodeSame(404, 'A link is used once.');

    $crawler = $this->client->request('GET', '/austral-admin/login');
    $this->client->submit($crawler->filter('form')->form(['_username' => 'admin', '_password' => 'Abcdefg1']));
    self::assertResponseRedirects('/austral-admin/');
  }

  public function testAnInvalidResetLinkIsNotFound(): void
  {
    $this->client->request('GET', '/austral-admin/password/reset/'.str_repeat('a', 96));
    self::assertResponseStatusCodeSame(404);
  }

  public function testMyAccountNeedsTheCurrentPassword(): void
  {
    $this->login('admin');
    $crawler = $this->client->request('GET', '/austral-admin/me');
    self::assertResponseIsSuccessful();
    self::assertCount(0, $crawler->filter('[name="account[typeUser]"]'), 'The type is not in my account.');
    self::assertCount(0, $crawler->filter('[name^="account[groups]"]'));

    $form = $crawler->filter('form[name="account"]')->form(['account[firstname]' => 'Jane', 'account[email]' => 'other@austral.dev', 'account[currentPassword]' => 'wrong']);
    $this->client->submit($form);
    self::assertResponseStatusCodeSame(422);
    $this->doctrine()->clear();
    self::assertSame('admin@austral.dev', $this->findUser('admin')->getEmail());

    $crawler = $this->client->request('GET', '/austral-admin/me');
    self::assertResponseIsSuccessful('The user of the session keeps his values.');
    $form = $crawler->filter('form[name="account"]')->form(['account[firstname]' => 'Jane', 'account[currentPassword]' => 'secret']);
    $this->client->submit($form);
    self::assertResponseRedirects('/austral-admin/me');
    $this->client->followRedirect();
    self::assertResponseIsSuccessful('Still logged in.');
    $this->doctrine()->clear();
    self::assertSame('Jane', $this->findUser('admin')->getFirstname());
  }

  public function testTheInterfaceIsInTheLanguageOfTheUser(): void
  {
    $root = $this->findUser('root');
    $root->setLanguage('fr');
    $this->doctrine()->flush();
    $this->login('root');

    $crawler = $this->client->request('GET', '/austral-admin/');
    self::assertResponseIsSuccessful();
    self::assertSame('fr', $crawler->filter('html')->attr('lang'));
    self::assertStringContainsString('Tableau de bord', $crawler->filter('h1')->text());
  }

  private function collector(): MailCollector
  {
    /* @var MailCollector */
    return static::getContainer()->get(MailCollector::class);
  }
}
