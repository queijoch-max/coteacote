<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** EF-01 */
final class InscriptionInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Indiquez votre prénom.')]
        #[Assert\Length(max: 60)]
        #[Assert\Regex(pattern: "/^[\\p{L}' -]+$/u", message: 'Le prénom ne doit contenir que des lettres.')]
        public readonly string $prenom = '',

        #[Assert\NotBlank(message: 'Indiquez votre nom.')]
        #[Assert\Length(max: 60)]
        #[Assert\Regex(pattern: "/^[\\p{L}' -]+$/u", message: 'Le nom ne doit contenir que des lettres.')]
        public readonly string $nom = '',

        #[Assert\NotBlank(message: 'Indiquez votre adresse e-mail.')]
        #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
        #[Assert\Length(max: 180)]
        public readonly string $email = '',

        #[Assert\NotBlank(message: 'Choisissez un mot de passe.')]
        #[Assert\Length(min: 12, max: 4096, minMessage: 'Le mot de passe doit contenir au moins 12 caractères.')]
        public readonly string $password = '',

        #[Assert\NotBlank(message: 'Indiquez le nom de votre rue.')]
        #[Assert\Length(max: 120)]
        #[Assert\Regex(pattern: "/^[\\p{L}' -]+$/u", message: 'Indiquez seulement le nom de la rue, sans numéro.')]
        public readonly string $rue = '',

        #[Assert\IsTrue(message: 'Vous devez accepter les conditions d\'utilisation.')]
        public readonly bool $cgu = false,
    ) {
    }
}
