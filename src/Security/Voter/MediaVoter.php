<?php

namespace App\Security\Voter;

use App\Entity\Media;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Un invité ne peut gérer que ses propres médias ; Ina (admin) peut tout gérer.
 *
 * @extends Voter<string, Media>
 */
final class MediaVoter extends Voter
{
    public const MANAGE = 'MEDIA_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute && $subject instanceof Media;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $subject->getUser()?->getId() === $user->getId();
    }
}
