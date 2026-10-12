<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CategorieInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Indiquez un libellé.')]
        #[Assert\Length(max: 80)]
        public readonly string $libelle = '',

        #[Assert\Positive]
        public readonly ?int $parentId = null,

        public readonly bool $actif = true,

        #[Assert\PositiveOrZero]
        public readonly int $ordre = 0,
    ) {
    }
}
