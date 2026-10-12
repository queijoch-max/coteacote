<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class MotInterditInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Indiquez un mot.')]
        #[Assert\Length(min: 2, max: 60)]
        #[Assert\Regex(pattern: '/^[\p{L}\p{N}-]+$/u', message: 'Un seul mot, sans espace.')]
        public readonly string $mot = '',

        public readonly bool $actif = true,
    ) {
    }
}
