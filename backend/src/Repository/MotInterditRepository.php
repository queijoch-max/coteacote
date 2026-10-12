<?php

namespace App\Repository;

use App\Entity\MotInterdit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MotInterdit>
 */
class MotInterditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MotInterdit::class);
    }

    /** @return string[] */
    public function findMotsActifs(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.mot')
            ->andWhere('m.actif = true')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'mot');
    }
}
