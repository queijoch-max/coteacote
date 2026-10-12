<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/** La déconnexion répond en JSON au lieu de rediriger. */
#[AsEventListener]
class LogoutSubscriber
{
    public function __invoke(LogoutEvent $event): void
    {
        $event->setResponse(new JsonResponse(['message' => 'Vous êtes déconnecté.']));
    }
}
