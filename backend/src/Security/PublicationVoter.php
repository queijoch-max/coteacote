<?php

namespace App\Security;

use App\Entity\Publication;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * ES-03 : droits qui dépendent de la publication ou de l'état du compte.
 *
 * Un habitant en attente, suspendu ou en cours de suppression ne peut ni publier, ni répondre,
 * ni signaler, même en appelant l'API directement.
 */
class PublicationVoter extends Voter
{
    public const CREER = 'PUBLICATION_CREER';
    public const REPONDRE = 'PUBLICATION_REPONDRE';
    public const SIGNALER = 'PUBLICATION_SIGNALER';
    public const GERER = 'PUBLICATION_GERER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (self::CREER === $attribute) {
            return true;
        }

        return \in_array($attribute, [self::REPONDRE, self::SIGNALER, self::GERER], true)
            && $subject instanceof Publication;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (self::GERER === $attribute) {
            // Seul l'auteur gère (résout, renouvelle, supprime) sa publication.
            return $subject->getAuteur()?->getId() === $user->getId();
        }

        if (!$user->peutAgir()) {
            $vote?->addReason('Votre compte ne permet pas cette action.');

            return false;
        }

        if (self::CREER === $attribute) {
            return true;
        }

        /** @var Publication $subject */
        return $subject->isActive() && $subject->getAuteur()?->getId() !== $user->getId();
    }
}
