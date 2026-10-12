<?php

namespace App\Service;

use App\Entity\Categorie;
use App\Entity\Publication;
use App\Entity\User;

/**
 * Transforme une publication en tableau JSON.
 *
 * EF-16 / ES-08 : les champs exposés sont listés explicitement, jamais l'entité complète.
 * Aucune coordonnée (e-mail, téléphone) n'est présente dans la sortie.
 */
class PublicationPresenter
{
    /**
     * @param int[] $repondues  publications auxquelles le lecteur a déjà répondu
     * @param int[] $signalees  publications que le lecteur a déjà signalées
     */
    public function presenter(Publication $p, ?User $lecteur = null, array $repondues = [], array $signalees = [], ?int $nbReponses = null): array
    {
        $estAuteur = $lecteur && $p->getAuteur()?->getId() === $lecteur->getId();

        $data = [
            'id' => $p->getId(),
            'type' => $p->getType()->value,
            'typeLibelle' => $p->getType()->libelle(),
            'nature' => $p->getNature()->value,
            'natureLibelle' => $p->getNature()->libelle(),
            'categorie' => self::categorie($p->getCategorie()),
            'complement' => $p->getComplement(),
            'auteur' => $p->isPublieeParMairie() ? $p->getBeneficiaire() : $p->getAuteur()->getNomAffiche(),
            'rue' => $p->isPublieeParMairie() ? $p->getRueBeneficiaire() : $p->getAuteur()->getRue(),
            'publieeParMairie' => $p->isPublieeParMairie(),
            'createdAt' => $p->getCreatedAt()->format(\DATE_ATOM),
            'estAuteur' => $estAuteur,
            'dejaRepondu' => \in_array($p->getId(), $repondues, true),
            'dejaSignale' => \in_array($p->getId(), $signalees, true),
        ];

        if ($estAuteur || null !== $nbReponses) {
            $data += [
                'statut' => $p->getStatut()->value,
                'statutLibelle' => $p->isActive() ? 'En ligne' : ($p->getStatut()->value === 'active' ? 'Expirée' : $p->getStatut()->libelle()),
                'expiresAt' => $p->getExpiresAt()->format(\DATE_ATOM),
                'renouvelable' => !$p->isRenouvelee() && \in_array($p->getStatut()->value, ['active', 'expiree'], true),
                'nbReponses' => $nbReponses ?? 0,
            ];
        }

        return $data;
    }

    public static function categorie(?Categorie $c): ?array
    {
        if (!$c) {
            return null;
        }

        return [
            'id' => $c->getId(),
            'libelle' => $c->getLibelle(),
            'parent' => $c->getParent()?->getLibelle(),
            'parentId' => $c->getParent()?->getId(),
        ];
    }
}
