<?php

namespace App\Repository;

use App\Entity\Publication;
use App\Entity\Signalement;
use App\Entity\User;
use App\Enum\StatutSignalement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signalement>
 */
class SignalementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signalement::class);
    }

    public function existe(Publication $publication, User $user): bool
    {
        return null !== $this->findOneBy(['publication' => $publication, 'user' => $user]);
    }

    public function countEnAttentePour(Publication $publication): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT s.user)')
            ->andWhere('s.publication = :p')
            ->andWhere('s.statut = :statut')
            ->setParameter('p', $publication)
            ->setParameter('statut', StatutSignalement::EnAttente)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Nombre de publications ayant au moins un signalement en attente. */
    public function countPublicationsSignalees(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT s.publication)')
            ->andWhere('s.statut = :statut')
            ->setParameter('statut', StatutSignalement::EnAttente)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Signalement[] signalements en attente, avec leur publication */
    public function findEnAttente(): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.publication', 'p')->addSelect('p')
            ->innerJoin('p.auteur', 'a')->addSelect('a')
            ->innerJoin('p.categorie', 'c')->addSelect('c')
            ->andWhere('s.statut = :statut')
            ->setParameter('statut', StatutSignalement::EnAttente)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function marquerTraites(Publication $publication): void
    {
        $this->createQueryBuilder('s')
            ->update()
            ->set('s.statut', ':traite')
            ->andWhere('s.publication = :p')
            ->setParameter('traite', StatutSignalement::Traite)
            ->setParameter('p', $publication)
            ->getQuery()
            ->execute();
    }

    /** @return int[] */
    public function idsSignalees(User $user, array $publicationIds): array
    {
        if (!$publicationIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.publication) AS id')
            ->andWhere('s.user = :user')
            ->andWhere('s.publication IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $publicationIds)
            ->getQuery()
            ->getScalarResult();

        return array_map('intval', array_column($rows, 'id'));
    }
}
