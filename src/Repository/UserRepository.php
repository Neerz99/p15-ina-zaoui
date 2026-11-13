<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    /**
     * Invités + nombre de médias en une requête (évite le N+1 sur /guests).
     *
     * @return list<array{guest: User, mediaCount: int}>
     */
    public function findGuestsWithMediaCount(bool $onlyActive = false): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u AS guest', 'COUNT(m.id) AS mediaCount')
            ->leftJoin('u.medias', 'm')
            ->where('u.admin = false')
            ->groupBy('u.id')
            ->orderBy('u.name', SortDirection::Ascending);

        if ($onlyActive) {
            $qb->andWhere('u.blocked = false');
        }

        return array_map(
            static fn (array $row): array => ['guest' => $row['guest'], 'mediaCount' => (int) $row['mediaCount']],
            $qb->getQuery()->getResult(),
        );
    }

    public function findActiveGuest(int $id): ?User
    {
        return $this->findOneBy(['id' => $id, 'admin' => false, 'blocked' => false]);
    }
}
