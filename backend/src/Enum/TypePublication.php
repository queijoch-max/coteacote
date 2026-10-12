<?php

namespace App\Enum;

enum TypePublication: string
{
    case Demande = 'demande';
    case Offre = 'offre';

    public function libelle(): string
    {
        return match ($this) {
            self::Demande => 'Je cherche',
            self::Offre => 'Je propose',
        };
    }
}
