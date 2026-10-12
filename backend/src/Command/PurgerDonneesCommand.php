<?php

namespace App\Command;

use App\Repository\JournalActionRepository;
use App\Repository\PublicationRepository;
use App\Service\GestionComptes;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * DP-05, RG-11, RG-12 : durées de conservation.
 *  - publications expirées, résolues ou supprimées : 6 mois ;
 *  - journal des actions : 1 an ;
 *  - comptes supprimés, refusés ou jamais confirmés : 30 jours.
 */
#[AsCommand(name: 'app:donnees:purger', description: 'Applique les durées de conservation des données')]
class PurgerDonneesCommand
{
    public function __construct(
        private readonly PublicationRepository $publications,
        private readonly JournalActionRepository $journal,
        private readonly GestionComptes $comptes,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $maintenant = new \DateTimeImmutable();

        $comptes = $this->comptes->purger($maintenant);
        $publications = $this->publications->purgerAnciennes($maintenant->modify('-6 months'));
        $journal = $this->journal->purger($maintenant->modify('-1 year'));

        $io->definitionList(
            ['Comptes effacés' => $comptes],
            ['Publications effacées' => $publications],
            ['Entrées du journal effacées' => $journal],
        );
        $io->success('Durées de conservation appliquées.');

        return Command::SUCCESS;
    }
}
