<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comptes : admin, 2 invités actifs, 1 invité bloqué, 1 invité sans photo.
 * Mot de passe commun : "password".
 */
class AppFixtures extends Fixture
{
    public const PASSWORD = 'password';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $albumNature = (new Album())->setName('Nature');
        $albumVilles = (new Album())->setName('Villes');
        $manager->persist($albumNature);
        $manager->persist($albumVilles);

        $ina = $this->createUser($manager, 'Ina Zaoui', 'ina@zaoui.com', admin: true);
        $active = $this->createUser($manager, 'Invité Actif', 'actif@example.com', description: 'Photographe de rue.');
        $second = $this->createUser($manager, 'Invité Second', 'second@example.com');
        $blocked = $this->createUser($manager, 'Invité Bloqué', 'bloque@example.com', blocked: true);
        $this->createUser($manager, 'Invité Sans Photo', 'sansphoto@example.com');

        $this->createMedia($manager, $ina, 'Ina - Montagne', 'uploads/fixture-ina-1.jpg', $albumNature);
        $this->createMedia($manager, $ina, 'Ina - Forêt', 'uploads/fixture-ina-2.jpg', $albumNature);
        $this->createMedia($manager, $ina, 'Ina - Paris', 'uploads/fixture-ina-3.jpg', $albumVilles);
        $this->createMedia($manager, $ina, 'Ina - Désert', 'uploads/fixture-ina-4.jpg');

        $this->createMedia($manager, $active, 'Actif - Rue 1', 'uploads/fixture-actif-1.jpg');
        $this->createMedia($manager, $active, 'Actif - Rue 2', 'uploads/fixture-actif-2.jpg');
        $this->createMedia($manager, $active, 'Actif - Lac', 'uploads/fixture-actif-3.jpg', $albumNature);

        $this->createMedia($manager, $second, 'Second - Pont', 'uploads/fixture-second-1.jpg');

        $this->createMedia($manager, $blocked, 'Bloqué - Secret', 'uploads/fixture-bloque-1.jpg', $albumNature);
        $this->createMedia($manager, $blocked, 'Bloqué - Caché', 'uploads/fixture-bloque-2.jpg');

        $manager->flush();
    }

    private function createUser(
        ObjectManager $manager,
        string $name,
        string $email,
        bool $admin = false,
        bool $blocked = false,
        ?string $description = null,
    ): User {
        $user = (new User())
            ->setName($name)
            ->setEmail($email)
            ->setAdmin($admin)
            ->setBlocked($blocked)
            ->setDescription($description);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));
        $manager->persist($user);

        return $user;
    }

    private function createMedia(ObjectManager $manager, User $user, string $title, string $path, ?Album $album = null): void
    {
        $media = new Media();
        $media->setUser($user);
        $media->setTitle($title);
        $media->setPath($path);
        $media->setAlbum($album);
        $manager->persist($media);
    }
}
