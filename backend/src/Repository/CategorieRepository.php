<?php

namespace App\Repository;

use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categorie>
 */
class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    /** @return Categorie[] catégories principales, avec leurs sous-catégories */
    public function findRacines(bool $actifsSeulement = true): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.enfants', 'e')
            ->addSelect('e')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.ordre', 'ASC')
            ->addOrderBy('e.ordre', 'ASC');

        if ($actifsSeulement) {
            $qb->andWhere('c.actif = true');
        }

        return $qb->getQuery()->getResult();
    }
}
