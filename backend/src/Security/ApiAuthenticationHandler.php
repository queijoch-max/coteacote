<?php

namespace App\Security;

use App\Entity\User;
use App\Service\UserPresenter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Réponses JSON pour la connexion, l'absence de connexion et les accès refusés.
 */
class ApiAuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface, AuthenticationEntryPointInterface, AccessDeniedHandlerInterface
{
    public function __construct(private readonly UserPresenter $userPresenter)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var User $user */
        $user = $token->getUser();

        return new JsonResponse($this->userPresenter->presenter($user));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = $exception instanceof TooManyLoginAttemptsAuthenticationException
            ? 'Trop de tentatives de connexion. Réessayez dans 15 minutes.'
            : 'Adresse e-mail ou mot de passe incorrect.';

        return new JsonResponse(['message' => $message], $exception instanceof TooManyLoginAttemptsAuthenticationException ? 429 : 401);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['message' => 'Vous devez être connecté.'], 401);
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        return new JsonResponse(['message' => 'Vous n\'avez pas accès à cette action.'], 403);
    }
}
