<?php

namespace App\Enum;

enum MotifSignalement: string
{
    case Inapproprie = 'inapproprie';
    case Commercial = 'commercial';
    case Dangereux = 'dangereux';
    case DonneesPersonnelles = 'donnees_personnelles';
    case Doublon = 'doublon';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::Inapproprie => 'Contenu inapproprié ou insultant',
            self::Commercial => 'Vente ou demande d\'argent',
            self::Dangereux => 'Activité dangereuse',
            self::DonneesPersonnelles => 'Contient des coordonnées',
            self::Doublon => 'Publication en double',
            self::Autre => 'Autre raison',
        };
    }
}
