<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** EF-06 */
final class ProfilInput
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

        #[Assert\NotBlank(message: 'Indiquez le nom de votre rue.')]
        #[Assert\Length(max: 120)]
        #[Assert\Regex(pattern: "/^[\\p{L}' -]+$/u", message: 'Indiquez seulement le nom de la rue, sans numéro.')]
        public readonly string $rue = '',

        #[Assert\Regex(pattern: '/^(?:\+33\s?|0)[1-9](?:[\s.-]?\d{2}){4}$/', message: 'Ce numéro de téléphone n\'est pas valide.')]
        public readonly ?string $telephone = null,

        public readonly bool $partageTelephone = false,

        public readonly bool $partageEmail = true,
    ) {
    }

    #[Assert\Callback]
    public function verifierContact(ExecutionContextInterface $context): void
    {
        if (!$this->partageTelephone && !$this->partageEmail) {
            $context->buildViolation('Choisissez au moins un moyen de contact à partager.')
                ->atPath('partageEmail')->addViolation();
        }
        if ($this->partageTelephone && !$this->telephone) {
            $context->buildViolation('Indiquez votre numéro pour pouvoir le partager.')
                ->atPath('telephone')->addViolation();
        }
    }
}
