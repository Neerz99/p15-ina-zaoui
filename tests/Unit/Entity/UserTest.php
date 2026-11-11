<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Media;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class UserTest extends TestCase
{
    public function testAdminHasAdminAndUserRoles(): void
    {
        $user = (new User())->setAdmin(true);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testGuestOnlyHasUserRole(): void
    {
        self::assertSame(['ROLE_USER'], (new User())->getRoles());
    }

    public function testEmailIsTheUserIdentifier(): void
    {
        $user = (new User())->setEmail('ina@zaoui.com');

        self::assertSame('ina@zaoui.com', $user->getUserIdentifier());
    }

    public function testGuestIsNotBlockedByDefault(): void
    {
        self::assertFalse((new User())->isBlocked());
    }

    public function testAccessorsAndMediasCollection(): void
    {
        $user = (new User())->setName('Invité')->setDescription('Bio')->setPassword('hash');
        $user->getMedias()->add(new Media());

        self::assertSame('Invité', $user->getName());
        self::assertSame('Bio', $user->getDescription());
        self::assertSame('hash', $user->getPassword());
        self::assertCount(1, $user->getMedias());
    }

    public function testSessionIsInvalidatedWhenUserGetsBlocked(): void
    {
        $original = $this->makeUser();
        $refreshed = $this->makeUser()->setBlocked(true);

        self::assertFalse($original->isEqualTo($refreshed));
    }

    public function testSessionIsKeptWhenNothingChanged(): void
    {
        self::assertTrue($this->makeUser()->isEqualTo($this->makeUser()));
    }

    public function testSessionIsInvalidatedWhenPasswordChanged(): void
    {
        self::assertFalse($this->makeUser()->isEqualTo($this->makeUser()->setPassword('other-hash')));
    }

    public function testSessionIsInvalidatedForAnotherUserClass(): void
    {
        self::assertFalse($this->makeUser()->isEqualTo(new InMemoryUser('ina', 'hash')));
    }

    public function testSerializationDoesNotExposeThePasswordHash(): void
    {
        $serialized = serialize($this->makeUser());

        self::assertStringNotContainsString('$2y$13$secret-hash', $serialized);

        /** @var User $unserialized */
        $unserialized = unserialize($serialized);
        self::assertTrue($unserialized->isEqualTo($this->makeUser()));
    }

    private function makeUser(): User
    {
        return (new User())->setEmail('invite@example.com')->setPassword('$2y$13$secret-hash');
    }
}
