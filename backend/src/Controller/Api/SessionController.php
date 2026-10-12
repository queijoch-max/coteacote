<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\EventSubscriber\CsrfSubscriber;
use App\Service\UserPresenter;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * ES-02 : authentification par session (cookie HttpOnly, SameSite=Lax).
 */
#[OA\Tag(name: 'Session')]
class SessionController extends AbstractController
{
    /** Point d'entrée du front : utilisateur connecté (ou null) et jeton CSRF. */
    #[Route('/api/session', name: 'api_session', methods: ['GET'])]
    public function session(Request $request, CsrfTokenManagerInterface $csrf, UserPresenter $presenter): JsonResponse
    {
        $request->getSession()->start();
        $user = $this->getUser();

        return $this->json([
            'user' => $user instanceof User ? $presenter->presenter($user) : null,
            'csrfToken' => $csrf->getToken(CsrfSubscriber::TOKEN_ID)->getValue(),
        ]);
    }

    /** Traité par le pare-feu (json_login) : { "email": "...", "password": "..." }. */
    #[Route('/api/connexion', name: 'api_connexion', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string'),
    ]))]
    public function connexion(): never
    {
        throw new \LogicException('Intercepté par le pare-feu.');
    }

    #[Route('/api/deconnexion', name: 'api_deconnexion', methods: ['POST'])]
    public function deconnexion(): never
    {
        throw new \LogicException('Intercepté par le pare-feu.');
    }
}
