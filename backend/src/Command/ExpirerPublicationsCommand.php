<?php

namespace App\Command;

use App\Repository\PublicationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** EF-12 : les publications expirent automatiquement après 30 jours. */
#[AsCommand(name: 'app:publications:expirer', description: 'Passe en « expirée » les publications de plus de 30 jours')]
class ExpirerPublicationsCommand
{
    public function __construct(private readonly PublicationRepository $publications)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $nombre = $this->publications->expirer();
        $io->success(\sprintf('%d publication(s) expirée(s).', $nombre));

        return Command::SUCCESS;
    }
}
