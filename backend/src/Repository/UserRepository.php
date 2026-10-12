<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\StatutUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    /** Inscriptions dont l'e-mail est confirmé et qui attendent la décision de la mairie. */
    public function findInscriptionsEnAttente(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.statut = :statut')
            ->andWhere('u.emailConfirme = true')
            ->setParameter('statut', StatutUser::EnAttente)
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countInscriptionsEnAttente(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.statut = :statut')
            ->andWhere('u.emailConfirme = true')
            ->setParameter('statut', StatutUser::EnAttente)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return User[] */
    public function findHabitants(?string $recherche = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->andWhere('u.statut IN (:statuts)')
            ->setParameter('role', '%"'.User::ROLE_HABITANT.'"%')
            ->setParameter('statuts', [StatutUser::Valide->value, StatutUser::Suspendu->value])
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC');

        if ($recherche) {
            $qb->andWhere('LOWER(u.nom) LIKE :q OR LOWER(u.prenom) LIKE :q OR LOWER(u.rue) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($recherche).'%');
        }

        return $qb->getQuery()->getResult();
    }

    /** @return User[] */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * RG-11, RG-12, DP-05 : comptes à effacer définitivement.
     *
     * @return User[]
     */
    public function findAPurger(\DateTimeImmutable $limite): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere(
                '(u.statut = :supprime AND u.deletedAt < :limite)'
                .' OR (u.statut = :refuse AND u.statutChangeAt < :limite)'
                .' OR (u.statut = :attente AND u.emailConfirme = false AND u.createdAt < :limite)'
            )
            ->setParameter('supprime', StatutUser::Supprime)
            ->setParameter('refuse', StatutUser::Refuse)
            ->setParameter('attente', StatutUser::EnAttente)
            ->setParameter('limite', $limite)
            ->getQuery()
            ->getResult();
    }
}
