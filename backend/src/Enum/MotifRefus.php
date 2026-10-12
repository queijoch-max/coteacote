<?php

namespace App\Enum;

enum MotifRefus: string
{
    case HorsCommune = 'hors_commune';
    case IdentiteDouteuse = 'identite_douteuse';
    case Doublon = 'doublon';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::HorsCommune => 'Vous ne semblez pas habiter la commune.',
            self::IdentiteDouteuse => 'Les informations fournies n\'ont pas pu être vérifiées.',
            self::Doublon => 'Un compte existe déjà à votre nom.',
            self::Autre => 'Merci de contacter la mairie pour plus d\'informations.',
        };
    }
}
