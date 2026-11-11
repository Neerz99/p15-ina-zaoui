<?php

namespace App\Tests\Functional\Admin;

use App\Tests\Functional\AppWebTestCase;
use App\Tests\Support\ImageFactory;
use Symfony\Component\Filesystem\Filesystem;

final class MediaControllerTest extends AppWebTestCase
{
    protected function tearDown(): void
    {
        (new Filesystem())->remove(static::getContainer()->getParameter('app.public_dir'));
        parent::tearDown();
    }

    public function testInaSeesAllMedias(): void
    {
        $this->loginAs('ina@zaoui.com');
        $this->client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'tbody tr');
        self::assertSelectorExists('th:contains("Artiste")');
    }

    public function testGuestOnlySeesItsOwnMedias(): void
    {
        $this->loginAs('actif@example.com');
        $crawler = $this->client->request('GET', '/admin/media');

        self::assertSelectorCount(3, 'tbody tr');
        $titles = $crawler->filter('tbody tr td:nth-child(2)')->each(static fn ($n) => $n->text());
        self::assertSame(['Actif - Rue 1', 'Actif - Rue 2', 'Actif - Lac'], $titles);
        self::assertSelectorNotExists('th:contains("Artiste")');
    }

    public function testPaginationUses25MediasPerPage(): void
    {
        $ina = $this->loginAs('ina@zaoui.com');
        for ($i = 0; $i < 20; ++$i) {
            $media = new \App\Entity\Media();
            $media->setTitle("Extra $i");
            $media->setPath("uploads/extra-$i.jpg");
            $media->setUser($ina);
            $this->em()->persist($media);
        }
        $this->em()->flush();

        $this->client->request('GET', '/admin/media');
        self::assertSelectorCount(25, 'tbody tr');
        self::assertSelectorExists('.pagination a:contains("Suivant")');

        $this->client->request('GET', '/admin/media?page=2');
        self::assertSelectorCount(5, 'tbody tr');
    }

    public function testGuestCanUploadAValidImage(): void
    {
        $guest = $this->loginAs('second@example.com');

        $this->submitMedia('Nouvelle photo', ImageFactory::png());

        self::assertResponseRedirects('/admin/media');
        $media = $this->media('Nouvelle photo');
        self::assertSame($guest->getId(), $media->getUser()?->getId());
        self::assertFileExists(static::getContainer()->getParameter('app.public_dir').'/'.$media->getPath());
    }

    public function testInaCanUploadForAGuestInAnAlbum(): void
    {
        $this->loginAs('ina@zaoui.com');
        $crawler = $this->client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form();
        $form['media[title]'] = 'Photo admin';
        $form['media[user]']->select((string) $this->user('second@example.com')->getId());
        $form['media[album]']->select((string) $this->album('Villes')->getId());
        $form['media[file]']->upload(ImageFactory::png()->getPathname());
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/media');
        $media = $this->media('Photo admin');
        self::assertSame('Villes', $media->getAlbum()?->getName());
        self::assertSame('second@example.com', $media->getUser()?->getEmail());
    }

    public function testUploadRejectsNonImageFile(): void
    {
        $this->loginAs('second@example.com');

        $this->submitMedia('Faux', ImageFactory::fakeImage());

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'doit être une image');
    }

    public function testUploadRejectsFileOver2Mb(): void
    {
        $this->loginAs('second@example.com');

        $this->submitMedia('Trop gros', ImageFactory::bigJpeg());

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'trop volumineux');
    }

    public function testGuestCanDeleteItsOwnMedia(): void
    {
        $this->loginAs('actif@example.com');
        $id = $this->media('Actif - Rue 1')->getId();

        $this->client->request('GET', '/admin/media');
        $this->client->submit($this->client->getCrawler()->filter('form[action="/admin/media/delete/'.$id.'"]')->form());

        self::assertResponseRedirects('/admin/media');
        $this->em()->clear();
        self::assertNull($this->em()->find(\App\Entity\Media::class, $id));
    }

    public function testGuestCannotDeleteAnotherGuestMedia(): void
    {
        $this->loginAs('actif@example.com');
        $other = $this->media('Second - Pont');

        $this->client->request('POST', '/admin/media/delete/'.$other->getId(), ['_token' => 'peu-importe']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteRequiresAValidCsrfToken(): void
    {
        $this->loginAs('ina@zaoui.com');

        $id = $this->media('Second - Pont')->getId();

        $this->client->request('POST', '/admin/media/delete/'.$id, ['_token' => 'invalide']);

        self::assertResponseRedirects('/login');
        $this->em()->clear();
        self::assertNotNull($this->em()->find(\App\Entity\Media::class, $id), 'Le média ne doit pas être supprimé sans jeton CSRF valide.');
    }

    public function testDeleteIsNotAllowedWithGet(): void
    {
        $this->loginAs('ina@zaoui.com');

        $this->client->request('GET', '/admin/media/delete/'.$this->media('Second - Pont')->getId());

        self::assertResponseStatusCodeSame(405);
    }

    private function submitMedia(string $title, \Symfony\Component\HttpFoundation\File\UploadedFile $file): void
    {
        $crawler = $this->client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form();
        $form['media[title]'] = $title;
        $form['media[file]']->upload($file->getPathname());
        $this->client->submit($form);
    }
}
