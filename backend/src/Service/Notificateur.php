<?php

namespace App\Service;

use App\Entity\Commune;
use App\Entity\Publication;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Tous les e-mails de l'application. En local, ils sont capturés par Mailpit (jamais envoyés réellement).
 */
class Notificateur
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(FRONT_URL)%')]
        private readonly string $frontUrl,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $from,
    ) {
    }

    /** EF-03 */
    public function confirmationEmail(User $user): void
    {
        $lien = $this->frontUrl.'/confirmer-email?token='.$user->getConfirmationToken();
        $this->logger->info('Lien de confirmation pour {email} : {lien}', ['email' => $user->getEmail(), 'lien' => $lien]);

        $this->envoyer($user->getEmail(), 'Confirmez votre adresse e-mail', 'emails/confirmation.html.twig', [
            'user' => $user,
            'lien' => $lien,
        ]);
    }

    /** EF-05 */
    public function inscriptionValidee(User $user): void
    {
        $this->envoyer($user->getEmail(), 'Votre inscription est validée', 'emails/inscription_validee.html.twig', [
            'user' => $user,
            'lien' => $this->frontUrl.'/connexion',
        ]);
    }

    /** EF-05 */
    public function inscriptionRefusee(User $user): void
    {
        $this->envoyer($user->getEmail(), 'Votre demande d\'inscription', 'emails/inscription_refusee.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * EF-18 / EF-19 : seul l'auteur reçoit le contact choisi par la personne qui répond.
     * Pour une publication faite par la mairie (EF-26), l'e-mail part à l'adresse de contact de la mairie.
     */
    public function reponseRecue(Publication $publication, User $repondant): void
    {
        $auteur = $publication->getAuteur();
        $destinataire = $publication->isPublieeParMairie()
            ? $auteur->getCommune()->getEmailContact()
            : $auteur->getEmail();

        $this->envoyer($destinataire, $repondant->getPrenom().' souhaite vous répondre', 'emails/reponse.html.twig', [
            'publication' => $publication,
            'repondant' => $repondant,
            'telephone' => $repondant->isPartageTelephone() ? $repondant->getTelephone() : null,
            'emailContact' => $repondant->isPartageEmail() || !$repondant->getTelephone() ? $repondant->getEmail() : null,
        ]);
    }

    /** EF-27 */
    public function recapitulatifMairie(Commune $commune, int $inscriptions, int $signalements): void
    {
        $this->envoyer($commune->getEmailContact(), 'Entraide : actions en attente', 'emails/recapitulatif_mairie.html.twig', [
            'commune' => $commune,
            'inscriptions' => $inscriptions,
            'signalements' => $signalements,
            'lien' => $this->frontUrl.'/mairie',
        ]);
    }

    private function envoyer(string $destinataire, string $sujet, string $template, array $contexte): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->from, 'Côte à côte'))
            ->to($destinataire)
            ->subject($sujet)
            ->htmlTemplate($template)
            ->context($contexte);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // En local, Mailpit peut ne pas être lancé : on ne bloque pas l'action de l'utilisateur.
            $this->logger->error('Envoi d\'e-mail impossible : {message}', ['message' => $e->getMessage()]);
        }
    }
}
