<?php

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;

/**
 * Connexion chargée depuis la base de données (provider « entity »).
 */
final class SecurityControllerTest extends AppWebTestCase
{
    public function testLoginPageIsDisplayed(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Connexion');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testInaCanLogInWithHerEmail(): void
    {
        $this->submitLogin('ina@zaoui.com', AppFixtures::PASSWORD);

        self::assertResponseRedirects('/admin/media');
        $this->client->followRedirect();
        self::assertSelectorTextContains('nav', 'Invités');
    }

    public function testGuestCanLogIn(): void
    {
        $this->submitLogin('actif@example.com', AppFixtures::PASSWORD);

        self::assertResponseRedirects('/admin/media');
        $this->client->followRedirect();
        self::assertSelectorTextNotContains('nav', 'Albums');
    }

    public function testWrongPasswordIsRejected(): void
    {
        $this->submitLogin('ina@zaoui.com', 'mauvais-mot-de-passe');

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-danger');
    }

    public function testBlockedGuestCannotLogIn(): void
    {
        $this->submitLogin('bloque@example.com', AppFixtures::PASSWORD);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Votre accès a été révoqué');
    }

    public function testLoggedUserIsRedirectedFromLoginPage(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/login');

        self::assertResponseRedirects('/admin/media');
    }

    public function testLogout(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/logout');

        self::assertResponseRedirects('/');
        $this->client->request('GET', '/admin/media');
        self::assertResponseRedirects('/login');
    }

    public function testAdminAreaRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/media');

        self::assertResponseRedirects('/login');
    }

    public function testBlockingAGuestEndsItsSession(): void
    {
        $guest = $this->loginAs('actif@example.com');
        $this->client->request('GET', '/admin/media');
        self::assertResponseIsSuccessful();

        $guest->setBlocked(true);
        $this->em()->flush();

        $this->client->request('GET', '/admin/media');
        self::assertResponseRedirects('/login');
    }

    private function submitLogin(string $email, string $password): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Connexion', [
            '_username' => $email,
            '_password' => $password,
        ]);
    }
}
