<?php

namespace App\Tests\Unit\Security;

use App\Entity\Media;
use App\Entity\User;
use App\Security\Voter\MediaVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class MediaVoterTest extends TestCase
{
    /**
     * @return iterable<string, array{bool, bool, int}>
     */
    public static function cases(): iterable
    {
        yield 'Ina peut gérer le média d\'un invité' => [true, false, VoterInterface::ACCESS_GRANTED];
        yield 'un invité peut gérer son propre média' => [false, true, VoterInterface::ACCESS_GRANTED];
        yield 'un invité ne peut pas gérer le média d\'un autre' => [false, false, VoterInterface::ACCESS_DENIED];
    }

    #[DataProvider('cases')]
    public function testVote(bool $connectedIsAdmin, bool $isOwner, int $expected): void
    {
        $owner = $this->user(1, false);
        $connected = $isOwner ? $owner : $this->user(2, $connectedIsAdmin);
        $media = new Media();
        $media->setUser($owner);

        $token = new UsernamePasswordToken($connected, 'main', $connected->getRoles());

        self::assertSame($expected, (new MediaVoter())->vote($token, $media, [MediaVoter::MANAGE]));
    }

    public function testAnonymousIsDenied(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, (new MediaVoter())->vote(new NullToken(), new Media(), [MediaVoter::MANAGE]));
    }

    public function testAbstainsOnOtherAttributes(): void
    {
        $token = new UsernamePasswordToken($this->user(1, true), 'main');

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, (new MediaVoter())->vote($token, new Media(), ['OTHER']));
    }

    private function user(int $id, bool $admin): User
    {
        $user = (new User())->setEmail("user{$id}@example.com")->setAdmin($admin);
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }
}
