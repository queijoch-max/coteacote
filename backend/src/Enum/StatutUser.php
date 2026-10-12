<?php

namespace App\Enum;

enum StatutUser: string
{
    case EnAttente = 'en_attente';
    case Valide = 'valide';
    case Refuse = 'refuse';
    case Suspendu = 'suspendu';
    case Supprime = 'supprime';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de validation',
            self::Valide => 'Validé',
            self::Refuse => 'Refusé',
            self::Suspendu => 'Suspendu',
            self::Supprime => 'Suppression demandée',
        };
    }
}
