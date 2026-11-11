<?php

namespace App\Tests\Functional;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base des tests fonctionnels : les fixtures (AppFixtures) sont chargées une fois
 * dans la base de test et chaque test est isolé dans une transaction annulée
 * (DAMADoctrineTestBundle).
 */
abstract class AppWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function user(string $email): User
    {
        $user = $this->em()->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, "Utilisateur $email absent des fixtures.");

        return $user;
    }

    protected function media(string $title): Media
    {
        $media = $this->em()->getRepository(Media::class)->findOneBy(['title' => $title]);
        self::assertNotNull($media, "Média $title absent des fixtures.");

        return $media;
    }

    protected function album(string $name): Album
    {
        $album = $this->em()->getRepository(Album::class)->findOneBy(['name' => $name]);
        self::assertNotNull($album);

        return $album;
    }

    protected function loginAs(string $email): User
    {
        $user = $this->user($email);
        $this->client->loginUser($user);

        return $user;
    }
}
