<?php

namespace App\Service;

use App\Entity\JournalAction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * EF-28 : journal des actions de la mairie (et de l'administrateur association).
 * L'entrée est ajoutée à l'unité de travail : elle est enregistrée au prochain flush().
 */
class Journal
{
    public const VALIDATION_INSCRIPTION = 'validation_inscription';
    public const REFUS_INSCRIPTION = 'refus_inscription';
    public const SUSPENSION_COMPTE = 'suspension_compte';
    public const REACTIVATION_COMPTE = 'reactivation_compte';
    public const MASQUAGE_PUBLICATION = 'masquage_publication';
    public const RETABLISSEMENT_PUBLICATION = 'retablissement_publication';
    public const SUPPRESSION_PUBLICATION = 'suppression_publication';
    public const PUBLICATION_POUR_HABITANT = 'publication_pour_habitant';
    public const CREATION_COMPTE_MAIRIE = 'creation_compte_mairie';

    public const LIBELLES = [
        self::VALIDATION_INSCRIPTION => 'Inscription validée',
        self::REFUS_INSCRIPTION => 'Inscription refusée',
        self::SUSPENSION_COMPTE => 'Compte suspendu',
        self::REACTIVATION_COMPTE => 'Compte réactivé',
        self::MASQUAGE_PUBLICATION => 'Publication masquée',
        self::RETABLISSEMENT_PUBLICATION => 'Publication rétablie',
        self::SUPPRESSION_PUBLICATION => 'Publication supprimée',
        self::PUBLICATION_POUR_HABITANT => 'Publication pour un habitant sans Internet',
        self::CREATION_COMPTE_MAIRIE => 'Compte mairie créé',
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function enregistrer(User $auteur, string $action, string $cible, ?int $cibleId): void
    {
        $this->em->persist(new JournalAction($auteur, $action, $cible, $cibleId));
    }
}
