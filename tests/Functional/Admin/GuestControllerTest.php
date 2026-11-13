<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class GuestControllerTest extends AppWebTestCase
{
    public function testGuestManagementIsReservedToIna(): void
    {
        $this->loginAs('actif@example.com');

        $this->client->request('GET', '/admin/guest');

        self::assertResponseStatusCodeSame(403);
    }

    public function testInaSeesAllGuestsWithStatusAndMediaCount(): void
    {
        $this->loginAs('ina@zaoui.com');
        $crawler = $this->client->request('GET', '/admin/guest');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(4, 'tbody tr');
        $blockedRow = $crawler->filter('tbody tr:contains("Invité Bloqué")');
        self::assertStringContainsString('Bloqué', $blockedRow->filter('.badge')->text());
        self::assertStringContainsString('Débloquer', $blockedRow->filter('button')->first()->text());
        $activeRow = $crawler->filter('tbody tr:contains("Invité Actif")');
        self::assertSame('3', $activeRow->filter('td')->eq(2)->text());
        self::assertSame('Actif', $activeRow->filter('.badge')->text());
    }

    public function testInaCanAddAGuestWithAHashedPassword(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/admin/guest/add');
        $this->client->submitForm('Ajouter', [
            'guest[name]' => 'Nouvelle Invitée',
            'guest[email]' => 'nouvelle@example.com',
            'guest[description]' => 'Photographe animalière',
            'guest[plainPassword]' => 'motdepasse-solide',
        ]);

        self::assertResponseRedirects('/admin/guest');
        $guest = $this->user('nouvelle@example.com');
        self::assertFalse($guest->isAdmin());
        self::assertFalse($guest->isBlocked());
        self::assertNotSame('motdepasse-solide', $guest->getPassword());
        self::assertTrue(static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($guest, 'motdepasse-solide'));
    }

    public function testAddingAGuestValidatesTheForm(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/admin/guest/add');
        $this->client->submitForm('Ajouter', [
            'guest[name]' => '',
            'guest[email]' => 'actif@example.com',
            'guest[plainPassword]' => 'court',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Le nom est obligatoire.');
        self::assertSelectorTextContains('body', 'Cette adresse e-mail est déjà utilisée.');
        self::assertSelectorTextContains('body', 'au moins 8 caractères');
    }

    public function testInaCanBlockThenUnblockAGuest(): void
    {
        $this->loginAs('ina@zaoui.com');
        $guest = $this->user('actif@example.com');

        $this->clickRowButton('Invité Actif', 'Bloquer');
        self::assertResponseRedirects('/admin/guest');
        $this->em()->clear();
        self::assertTrue($this->user('actif@example.com')->isBlocked());

        $this->client->request('GET', '/guest/'.$guest->getId());
        self::assertResponseStatusCodeSame(404);

        $this->clickRowButton('Invité Actif', 'Débloquer');
        $this->em()->clear();
        self::assertFalse($this->user('actif@example.com')->isBlocked());
    }

    public function testDeletingAGuestDeletesItsMediasAndFiles(): void
    {
        $this->loginAs('ina@zaoui.com');
        $guest = $this->user('actif@example.com');
        $guestId = $guest->getId();
        $publicDir = static::getContainer()->getParameter('app.public_dir');
        $filesystem = new Filesystem();
        foreach ($guest->getMedias() as $media) {
            $filesystem->dumpFile($publicDir.'/'.$media->getPath(), 'image');
        }
        $paths = $guest->getMedias()->map(static fn (Media $m) => $publicDir.'/'.$m->getPath())->toArray();

        $this->clickRowButton('Invité Actif', 'Supprimer');

        self::assertResponseRedirects('/admin/guest');
        $this->em()->clear();
        self::assertNull($this->em()->find(User::class, $guestId));
        self::assertSame(0, $this->em()->getRepository(Media::class)->count(['user' => $guestId]));
        foreach ($paths as $path) {
            self::assertFileDoesNotExist($path);
        }
        $filesystem->remove($publicDir);
    }

    public function testInaCannotBeBlockedOrDeleted(): void
    {
        $this->loginAs('ina@zaoui.com');
        $inaId = $this->user('ina@zaoui.com')->getId();

        $this->client->request('POST', '/admin/guest/'.$inaId.'/delete', ['_token' => 'x']);
        self::assertResponseStatusCodeSame(404);

        $this->client->request('POST', '/admin/guest/'.$inaId.'/toggle-block', ['_token' => 'x']);
        self::assertResponseStatusCodeSame(404);
    }

    private function clickRowButton(string $guestName, string $button): void
    {
        $crawler = $this->client->request('GET', '/admin/guest');
        $form = $crawler->filter('tbody tr:contains("'.$guestName.'")')->selectButton($button)->form();
        $this->client->submit($form);
    }
}
