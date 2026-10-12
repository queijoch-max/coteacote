<?php

namespace App;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Tâches planifiées. À lancer en local avec : php bin/console messenger:consume scheduler_default
 * (ou exécuter chaque commande à la main).
 */
#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(
                RecurringMessage::cron('0 * * * *', new RunCommandMessage('app:publications:expirer')),
                RecurringMessage::cron('30 3 * * *', new RunCommandMessage('app:donnees:purger')),
                RecurringMessage::cron('0 8 * * *', new RunCommandMessage('app:mairie:recapitulatif')),
            );
    }
}
