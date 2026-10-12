<?php

namespace App\Repository;

use App\Entity\Categorie;
use App\Entity\Publication;
use App\Entity\Reponse;
use App\Entity\Signalement;
use App\Entity\User;
use App\Enum\NaturePublication;
use App\Enum\StatutPublication;
use App\Enum\TypePublication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public const PAR_PAGE = 20;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * EF-15 : liste des publications actives, filtrable.
     *
     * @return array{items: Publication[], total: int}
     */
    public function findActives(
        ?TypePublication $type,
        ?Categorie $categorie,
        ?NaturePublication $nature,
        int $page = 1,
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.auteur', 'a')->addSelect('a')
            ->innerJoin('p.categorie', 'c')->addSelect('c')
            ->leftJoin('c.parent', 'cp')->addSelect('cp')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.expiresAt > :now')
            ->setParameter('statut', StatutPublication::Active)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult((max(1, $page) - 1) * self::PAR_PAGE)
            ->setMaxResults(self::PAR_PAGE);

        if ($type) {
            $qb->andWhere('p.type = :type')->setParameter('type', $type);
        }
        if ($nature) {
            $qb->andWhere('p.nature = :nature')->setParameter('nature', $nature);
        }
        if ($categorie) {
            // Une catégorie principale inclut ses sous-catégories.
            $qb->andWhere('c = :cat OR c.parent = :cat')->setParameter('cat', $categorie);
        }

        $paginator = new Paginator($qb, fetchJoinCollection: false);

        return ['items' => iterator_to_array($paginator), 'total' => \count($paginator)];
    }

    /** @return Publication[] */
    public function findDeAuteur(User $auteur): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.categorie', 'c')->addSelect('c')
            ->andWhere('p.auteur = :auteur')
            ->andWhere('p.statut != :supprimee')
            ->setParameter('auteur', $auteur)
            ->setParameter('supprimee', StatutPublication::Supprimee)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** EF-14 */
    public function countActivesDe(User $auteur): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.auteur = :auteur')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.expiresAt > :now')
            ->setParameter('auteur', $auteur)
            ->setParameter('statut', StatutPublication::Active)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActives(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.expiresAt > :now')
            ->setParameter('statut', StatutPublication::Active)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** EF-25 : publications d'un statut créées depuis le début du mois. */
    public function countDuMois(StatutPublication $statut): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.createdAt >= :debut')
            ->setParameter('statut', $statut)
            ->setParameter('debut', new \DateTimeImmutable('first day of this month 00:00'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** EF-12 : passe en « expirée » les publications actives dont la date est dépassée. */
    public function expirer(): int
    {
        return $this->createQueryBuilder('p')
            ->update()
            ->set('p.statut', ':expiree')
            ->andWhere('p.statut = :active')
            ->andWhere('p.expiresAt <= :now')
            ->setParameter('expiree', StatutPublication::Expiree)
            ->setParameter('active', StatutPublication::Active)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /** DP-05 : publications expirées, résolues ou supprimées depuis plus de 6 mois. */
    public function purgerAnciennes(\DateTimeImmutable $limite): int
    {
        $ids = $this->createQueryBuilder('p')
            ->select('p.id')
            ->andWhere('p.statut IN (:statuts)')
            ->andWhere('p.expiresAt < :limite')
            ->setParameter('statuts', [
                StatutPublication::Expiree->value,
                StatutPublication::Resolue->value,
                StatutPublication::Supprimee->value,
            ])
            ->setParameter('limite', $limite)
            ->getQuery()
            ->getSingleColumnResult();

        if (!$ids) {
            return 0;
        }

        $em = $this->getEntityManager();
        foreach ([Reponse::class, Signalement::class, Publication::class] as $classe) {
            $champ = Publication::class === $classe ? 'x.id' : 'x.publication';
            $em->createQueryBuilder()->delete($classe, 'x')
                ->where($champ.' IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute();
        }

        return \count($ids);
    }

    /** EF-07 : suppression des publications d'un habitant qui supprime son compte. */
    public function supprimerDe(User $auteur): int
    {
        return $this->createQueryBuilder('p')
            ->update()
            ->set('p.statut', ':supprimee')
            ->andWhere('p.auteur = :auteur')
            ->setParameter('supprimee', StatutPublication::Supprimee)
            ->setParameter('auteur', $auteur)
            ->getQuery()
            ->execute();
    }
}
