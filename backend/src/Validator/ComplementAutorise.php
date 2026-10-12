<?php

namespace App\Validator;

use App\Service\ComplementFilter;
use Symfony\Component\Validator\Constraint;

/**
 * ES-04 : contrainte de validation personnalisée appliquant les règles RG-01 à RG-09 côté serveur.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class ComplementAutorise extends Constraint
{
    public string $message = ComplementFilter::MESSAGE;
}
