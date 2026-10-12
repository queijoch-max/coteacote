<?php

namespace App\Service;

use App\Entity\User;

/**
 * Données du compte connecté, renvoyées uniquement à lui-même.
 */
class UserPresenter
{
    public function presenter(User $user): array
    {
        return [
            'id' => $user->getId(),
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'email' => $user->getEmail(),
            'rue' => $user->getRue(),
            'role' => $user->getRole(),
            'statut' => $user->getStatut()->value,
            'statutLibelle' => $user->getStatut()->libelle(),
            'motifRefus' => $user->getMotifRefus()?->libelle(),
            'emailConfirme' => $user->isEmailConfirme(),
            'telephone' => $user->getTelephone(),
            'partageTelephone' => $user->isPartageTelephone(),
            'partageEmail' => $user->isPartageEmail(),
            'peutAgir' => $user->peutAgir(),
            'suppressionDefinitiveLe' => $user->getDeletedAt()?->modify('+'.GestionComptes::DELAI_JOURS.' days')->format(\DATE_ATOM),
            'commune' => $user->getCommune()?->getNom(),
        ];
    }
}
