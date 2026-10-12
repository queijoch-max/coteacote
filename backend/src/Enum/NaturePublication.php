<?php

namespace App\Enum;

enum NaturePublication: string
{
    case Don = 'don';
    case Pret = 'pret';
    case Aide = 'aide';

    public function libelle(): string
    {
        return match ($this) {
            self::Don => 'Don',
            self::Pret => 'Prêt',
            self::Aide => 'Aide ponctuelle',
        };
    }
}
