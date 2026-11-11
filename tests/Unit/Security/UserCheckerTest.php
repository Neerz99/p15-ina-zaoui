<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function testBlockedGuestCannotLogIn(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre accès a été révoqué');

        (new UserChecker())->checkPreAuth((new User())->setBlocked(true));
    }

    public function testActiveGuestCanLogIn(): void
    {
        $checker = new UserChecker();
        $user = new User();

        $checker->checkPreAuth($user);
        $checker->checkPostAuth($user);

        $this->addToAssertionCount(1);
    }
}
