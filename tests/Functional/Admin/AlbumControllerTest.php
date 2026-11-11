<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Album;
use App\Tests\Functional\AppWebTestCase;

final class AlbumControllerTest extends AppWebTestCase
{
    public function testGuestCannotAccessAlbums(): void
    {
        $this->loginAs('actif@example.com');
        $this->client->request('GET', '/admin/album');

        self::assertResponseStatusCodeSame(403);
    }

    public function testInaCanListAlbums(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/admin/album');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(2, 'tbody tr');
    }

    public function testInaCanCreateAnAlbum(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/admin/album/add');
        $this->client->submitForm('Ajouter', ['album[name]' => 'Océans']);

        self::assertResponseRedirects('/admin/album');
        self::assertNotNull($this->em()->getRepository(Album::class)->findOneBy(['name' => 'Océans']));
    }

    public function testAlbumNameIsRequired(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/admin/album/add');
        $this->client->submitForm('Ajouter', ['album[name]' => '']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testInaCanRenameAnAlbum(): void
    {
        $this->loginAs('ina@zaoui.com');
        $album = $this->album('Villes');
        $this->client->request('GET', '/admin/album/update/'.$album->getId());
        $this->client->submitForm('Modifier', ['album[name]' => 'Métropoles']);

        self::assertResponseRedirects('/admin/album');
        $this->em()->clear();
        self::assertSame('Métropoles', $this->em()->find(Album::class, $album->getId())?->getName());
    }

    public function testDeletingAnAlbumKeepsItsMedias(): void
    {
        $this->loginAs('ina@zaoui.com');
        $album = $this->album('Nature');
        $mediaId = $this->media('Ina - Montagne')->getId();

        $crawler = $this->client->request('GET', '/admin/album');
        $this->client->submit($crawler->filter('form[action="/admin/album/delete/'.$album->getId().'"]')->form());

        self::assertResponseRedirects('/admin/album');
        $this->em()->clear();
        self::assertNull($this->em()->find(Album::class, $album->getId()));
        $media = $this->em()->find(\App\Entity\Media::class, $mediaId);
        self::assertNotNull($media);
        self::assertNull($media->getAlbum());
    }
}
