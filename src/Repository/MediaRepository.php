<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Exclut les médias des invités bloqués.
     *
     * @return list<Media>
     */
    public function findVisibleByAlbum(Album $album): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->where('m.album = :album')
            ->andWhere('u.id IS NULL OR u.blocked = false')
            ->setParameter('album', $album)
            ->orderBy('m.id', SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste admin paginée, user et album joints.
     *
     * @return list<Media>
     */
    public function findPageForAdmin(?User $owner, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')->addSelect('u')
            ->leftJoin('m.album', 'a')->addSelect('a')
            ->orderBy('m.id', SortDirection::Ascending)
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage);

        if (null !== $owner) {
            $qb->where('m.user = :owner')->setParameter('owner', $owner);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Media>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.id', SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }
}
