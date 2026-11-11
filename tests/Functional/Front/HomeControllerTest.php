<?php

namespace App\Tests\Functional\Front;

use App\Tests\Functional\AppWebTestCase;

/**
 * Tests du Front Office : accueil, invités, page invité, portfolio, à propos.
 */
final class HomeControllerTest extends AppWebTestCase
{
    public function testHomePage(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2.home-title', 'Photographe');
        self::assertSelectorExists('a[href="/portfolio"]');
    }

    public function testNavigationLinksArePresent(): void
    {
        $crawler = $this->client->request('GET', '/');

        $links = $crawler->filter('header .nav-link')->each(static fn ($node) => $node->text());
        self::assertSame(['Invités', 'Portfolio', 'Qui suis-je ?', 'Connexion'], $links);
    }

    public function testGuestsPageListsActiveGuestsWithMediaCount(): void
    {
        $crawler = $this->client->request('GET', '/guests');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Invités');

        $guests = $crawler->filter('.guest h4')->each(static fn ($node) => $node->text());
        self::assertContains('Invité Actif (3)', $guests);
        self::assertContains('Invité Second (1)', $guests);
        self::assertContains('Invité Sans Photo (0)', $guests);
    }

    public function testGuestsPageHidesBlockedGuestsAndIna(): void
    {
        $this->client->request('GET', '/guests');

        self::assertSelectorTextNotContains('.guests', 'Invité Bloqué');
        self::assertSelectorTextNotContains('.guests', 'Ina Zaoui');
        self::assertSelectorCount(3, '.guest');
    }

    public function testGuestsPageNeedsASingleSqlQueryForGuests(): void
    {
        $this->client->enableProfiler();
        $this->client->request('GET', '/guests');

        $profile = $this->client->getProfile();
        self::assertInstanceOf(\Symfony\Component\HttpKernel\Profiler\Profile::class, $profile);
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(\Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector::class, $collector);
        self::assertSame(1, $collector->getQueryCount(), 'La page Invités ne doit faire qu\'une requête (pas de N+1).');
    }

    public function testGuestPageShowsGuestAndPhotos(): void
    {
        $guest = $this->user('actif@example.com');

        $this->client->request('GET', '/guest/'.$guest->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Invité Actif');
        self::assertSelectorTextContains('p', 'Photographe de rue.');
        self::assertSelectorCount(3, '.media img');
    }

    public function testBlockedGuestPageIsNotFound(): void
    {
        $this->client->request('GET', '/guest/'.$this->user('bloque@example.com')->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminIsNotAGuest(): void
    {
        $this->client->request('GET', '/guest/'.$this->user('ina@zaoui.com')->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testUnknownGuestIsNotFound(): void
    {
        $this->client->request('GET', '/guest/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPortfolioShowsInaPhotosByDefault(): void
    {
        $crawler = $this->client->request('GET', '/portfolio');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Portfolio');
        self::assertSelectorExists('a.active:contains("Toutes")');
        $titles = $crawler->filter('.media-title')->each(static fn ($node) => $node->text());
        self::assertSame(['Ina - Montagne', 'Ina - Forêt', 'Ina - Paris', 'Ina - Désert'], $titles);
    }

    public function testPortfolioAlbumFilterHidesBlockedGuestPhotos(): void
    {
        $album = $this->album('Nature');

        $crawler = $this->client->request('GET', '/portfolio/'.$album->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('a.active', 'Nature');
        $titles = $crawler->filter('.media-title')->each(static fn ($node) => $node->text());
        self::assertSame(['Ina - Montagne', 'Ina - Forêt', 'Actif - Lac'], $titles);
        self::assertNotContains('Bloqué - Secret', $titles);
    }

    public function testPortfolioListsAllAlbums(): void
    {
        $crawler = $this->client->request('GET', '/portfolio');

        $albums = $crawler->filter('.mb-5 a')->each(static fn ($node) => trim($node->text()));
        self::assertSame(['Toutes', 'Nature', 'Villes'], $albums);
    }

    public function testUnknownAlbumIsNotFound(): void
    {
        $this->client->request('GET', '/portfolio/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAboutPage(): void
    {
        $this->client->request('GET', '/about');

        self::assertResponseIsSuccessful();
    }
}
