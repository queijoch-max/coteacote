<?php

namespace App\Service;

use App\Entity\JournalAction;
use App\Entity\Publication;
use App\Entity\Reponse;
use App\Entity\Signalement;
use App\Entity\User;
use App\Enum\StatutUser;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Suppression des comptes : demande par l'habitant (EF-07, RG-11) puis effacement définitif (RG-12, DP-05).
 */
class GestionComptes
{
    public const DELAI_JOURS = 30;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly PublicationRepository $publicationRepository,
    ) {
    }

    /** EF-07 : les publications sont supprimées tout de suite, le compte est effacé après 30 jours. */
    public function demanderSuppression(User $user): void
    {
        if (StatutUser::Supprime !== $user->getStatut()) {
            $user->setStatutAvantSuppression($user->getStatut());
        }
        $user->setStatut(StatutUser::Supprime);
        $user->setDeletedAt(new \DateTimeImmutable());
        $this->publicationRepository->supprimerDe($user);
        $this->em->flush();
    }

    /** RG-11 : l'habitant peut annuler pendant 30 jours (ses publications restent supprimées). */
    public function annulerSuppression(User $user): void
    {
        $user->setStatut($user->getStatutAvantSuppression() ?? StatutUser::Valide);
        $user->setStatutAvantSuppression(null);
        $user->setDeletedAt(null);
        $this->em->flush();
    }

    /** @return int nombre de comptes effacés */
    public function purger(\DateTimeImmutable $maintenant = new \DateTimeImmutable()): int
    {
        $limite = $maintenant->modify('-'.self::DELAI_JOURS.' days');
        $comptes = $this->userRepository->findAPurger($limite);

        foreach ($comptes as $user) {
            $this->effacer($user);
        }
        $this->em->flush();

        return \count($comptes);
    }

    /**
     * Effacement définitif. Les dépendances sont supprimées explicitement
     * (SQLite n'applique pas les clés étrangères par défaut).
     */
    private function effacer(User $user): void
    {
        $publications = $this->em->createQueryBuilder()
            ->select('p.id')->from(Publication::class, 'p')
            ->where('p.auteur = :u')->setParameter('u', $user)
            ->getQuery()->getSingleColumnResult();

        foreach ([Reponse::class, Signalement::class] as $classe) {
            $qb = $this->em->createQueryBuilder()->delete($classe, 'x')->where('x.user = :u')->setParameter('u', $user);
            if ($publications) {
                $qb->orWhere('x.publication IN (:ids)')->setParameter('ids', $publications);
            }
            $qb->getQuery()->execute();
        }

        $this->em->createQueryBuilder()->delete(Publication::class, 'p')
            ->where('p.auteur = :u')->setParameter('u', $user)
            ->getQuery()->execute();

        $this->em->createQueryBuilder()->update(JournalAction::class, 'j')
            ->set('j.user', 'NULL')
            ->where('j.user = :u')->setParameter('u', $user)
            ->getQuery()->execute();

        $this->em->remove($user);
    }
}
