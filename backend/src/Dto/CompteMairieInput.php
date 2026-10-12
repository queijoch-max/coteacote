<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CompteMairieInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Indiquez le prénom.')]
        #[Assert\Length(max: 60)]
        public readonly string $prenom = '',

        #[Assert\NotBlank(message: 'Indiquez le nom.')]
        #[Assert\Length(max: 60)]
        public readonly string $nom = '',

        #[Assert\NotBlank(message: 'Indiquez l\'adresse e-mail.')]
        #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
        public readonly string $email = '',

        #[Assert\NotBlank(message: 'Choisissez un mot de passe.')]
        #[Assert\Length(min: 12, max: 4096, minMessage: 'Le mot de passe doit contenir au moins 12 caractères.')]
        public readonly string $password = '',
    ) {
    }
}
