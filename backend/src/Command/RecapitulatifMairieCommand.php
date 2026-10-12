<?php

namespace App\Command;

use App\Repository\CommuneRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Notificateur;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** EF-27 : au maximum un e-mail par jour, seulement s'il y a des actions en attente. */
#[AsCommand(name: 'app:mairie:recapitulatif', description: 'Envoie à la mairie le récapitulatif des actions en attente')]
class RecapitulatifMairieCommand
{
    private const CLE_CACHE = 'recapitulatif_mairie_dernier_envoi';

    public function __construct(
        private readonly CommuneRepository $communes,
        private readonly UserRepository $users,
        private readonly SignalementRepository $signalements,
        private readonly Notificateur $notificateur,
        private readonly CacheItemPoolInterface $cacheApp,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'Ignorer la limite d\'un envoi par jour')] bool $force = false): int
    {
        $dejaEnvoye = $this->cacheApp->getItem(self::CLE_CACHE);
        if (!$force && $dejaEnvoye->isHit()) {
            $io->note('Un récapitulatif a déjà été envoyé ces dernières 24 heures.');

            return Command::SUCCESS;
        }

        $inscriptions = $this->users->countInscriptionsEnAttente();
        $signalements = $this->signalements->countPublicationsSignalees();
        $commune = $this->communes->findCommune();

        if (!$commune || 0 === $inscriptions + $signalements) {
            $io->note('Aucune action en attente : pas d\'envoi.');

            return Command::SUCCESS;
        }

        $this->notificateur->recapitulatifMairie($commune, $inscriptions, $signalements);
        $this->cacheApp->save($dejaEnvoye->set(true)->expiresAfter(86400 - 300));

        $io->success(\sprintf('Récapitulatif envoyé : %d inscription(s), %d publication(s) signalée(s).', $inscriptions, $signalements));

        return Command::SUCCESS;
    }
}
