<?php

namespace App\Repository;

use App\Entity\JournalAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalAction>
 */
class JournalActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalAction::class);
    }

    /** @return JournalAction[] */
    public function findDernieres(int $limite = 100): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.user', 'u')->addSelect('u')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    /** DP-05 : journal conservé 1 an. */
    public function purger(\DateTimeImmutable $limite): int
    {
        return $this->createQueryBuilder('j')
            ->delete()
            ->andWhere('j.createdAt < :limite')
            ->setParameter('limite', $limite)
            ->getQuery()
            ->execute();
    }
}
