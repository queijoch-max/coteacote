<?php

namespace App\Dto;

use App\Enum\NaturePublication;
use App\Enum\TypePublication;
use App\Validator\ComplementAutorise;
use Symfony\Component\Validator\Constraints as Assert;

/** EF-08 à EF-11 : tout se choisit dans des listes, sauf le complément (30 caractères). */
final class PublicationInput
{
    public function __construct(
        #[Assert\NotNull(message: 'Choisissez « Je cherche » ou « Je propose ».')]
        public readonly ?TypePublication $type = null,

        #[Assert\NotNull(message: 'Choisissez une catégorie.')]
        #[Assert\Positive]
        public readonly ?int $categorieId = null,

        #[Assert\NotNull(message: 'Choisissez don, prêt ou aide ponctuelle.')]
        public readonly ?NaturePublication $nature = null,

        #[Assert\Length(max: 30, maxMessage: 'Le complément ne doit pas dépasser 30 caractères.')]
        #[ComplementAutorise]
        public readonly ?string $complement = null,
    ) {
    }
}
