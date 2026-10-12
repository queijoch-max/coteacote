<?php

namespace App\Enum;

enum StatutSignalement: string
{
    case EnAttente = 'en_attente';
    case Traite = 'traite';
}
