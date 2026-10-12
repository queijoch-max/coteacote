<?php

namespace App\Dto;

use App\Enum\NaturePublication;
use App\Enum\TypePublication;
use App\Validator\ComplementAutorise;
use Symfony\Component\Validator\Constraints as Assert;

/** EF-26 : publication saisie par la mairie pour un habitant sans Internet. */
final class PublicationMairieInput
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

        #[Assert\NotBlank(message: 'Indiquez le prénom de l\'habitant.')]
        #[Assert\Length(max: 60)]
        #[Assert\Regex(pattern: "/^[\\p{L}' -]+$/u", message: 'Le prénom ne doit contenir que des lettres.')]
        public readonly string $prenom = '',

        #[Assert\NotBlank(message: 'Indiquez l\'initiale du nom.')]
        #[Assert\Regex(pattern: '/^\p{L}$/u', message: 'Indiquez une seule lettre.')]
        public readonly string $initiale = '',

        #[Assert\NotBlank(message: 'Indiquez le nom de la rue.')]
        #[Assert\Length(max: 120)]
        #[Assert\Regex(pattern: "/^[\\p{L}' -]+$/u", message: 'Indiquez seulement le nom de la rue, sans numéro.')]
        public readonly string $rue = '',
    ) {
    }
}
