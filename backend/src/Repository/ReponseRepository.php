<?php

namespace App\Repository;

use App\Entity\Publication;
use App\Entity\Reponse;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reponse>
 */
class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    public function existe(Publication $publication, User $user): bool
    {
        return null !== $this->findOneBy(['publication' => $publication, 'user' => $user]);
    }

    /**
     * Identifiants des publications auxquelles l'habitant a déjà répondu, parmi une liste.
     *
     * @param int[] $publicationIds
     *
     * @return int[]
     */
    public function idsRepondues(User $user, array $publicationIds): array
    {
        if (!$publicationIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.publication) AS id')
            ->andWhere('r.user = :user')
            ->andWhere('r.publication IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $publicationIds)
            ->getQuery()
            ->getScalarResult();

        return array_map('intval', array_column($rows, 'id'));
    }

    /** @return array<int, int> nombre de réponses par publication */
    public function compterParPublication(array $publicationIds): array
    {
        if (!$publicationIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.publication) AS id, COUNT(r.id) AS nb')
            ->andWhere('r.publication IN (:ids)')
            ->setParameter('ids', $publicationIds)
            ->groupBy('r.publication')
            ->getQuery()
            ->getScalarResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = (int) $row['nb'];
        }

        return $result;
    }
}
