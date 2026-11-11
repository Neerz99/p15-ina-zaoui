<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    public function testFindGuestsWithMediaCountReturnsAllGuestsSortedByName(): void
    {
        $rows = $this->repository()->findGuestsWithMediaCount();

        $summary = array_map(static fn (array $r) => [$r['guest']->getName(), $r['mediaCount']], $rows);
        self::assertSame([
            ['Invité Actif', 3],
            ['Invité Bloqué', 2],
            ['Invité Sans Photo', 0],
            ['Invité Second', 1],
        ], $summary);
    }

    public function testFindGuestsWithMediaCountCanExcludeBlockedGuests(): void
    {
        $names = array_map(static fn (array $r) => $r['guest']->getName(), $this->repository()->findGuestsWithMediaCount(onlyActive: true));

        self::assertNotContains('Invité Bloqué', $names);
        self::assertCount(3, $names);
    }

    private function repository(): UserRepository
    {
        self::bootKernel();

        return static::getContainer()->get(UserRepository::class);
    }
}
