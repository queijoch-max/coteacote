<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * ES-05 : toute requête qui modifie des données doit porter l'en-tête X-CSRF-Token,
 * obtenu par GET /api/csrf et lié à la session.
 *
 * Priorité 9 : passe avant le pare-feu (priorité 8), donc avant le formulaire de connexion.
 */
class CsrfSubscriber implements EventSubscriberInterface
{
    public const TOKEN_ID = 'api';
    public const HEADER = 'X-CSRF-Token';

    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 9]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()
            || !str_starts_with($request->getPathInfo(), '/api/')
            || \in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = new CsrfToken(self::TOKEN_ID, (string) $request->headers->get(self::HEADER));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $event->setResponse(new JsonResponse(['message' => 'Session expirée. Rechargez la page.'], 403));
        }
    }
}
