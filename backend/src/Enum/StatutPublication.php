<?php

namespace App\Enum;

enum StatutPublication: string
{
    case Active = 'active';
    case Resolue = 'resolue';
    case Expiree = 'expiree';
    case Masquee = 'masquee';
    case Supprimee = 'supprimee';

    public function libelle(): string
    {
        return match ($this) {
            self::Active => 'En ligne',
            self::Resolue => 'Résolue',
            self::Expiree => 'Expirée',
            self::Masquee => 'Masquée',
            self::Supprimee => 'Supprimée',
        };
    }
}
