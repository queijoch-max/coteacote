<?php

namespace App\EventSubscriber;

use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Erreurs de l'API au format JSON : { message, errors? }.
 * Les erreurs de validation sont renvoyées champ par champ, en français.
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // Avant le gestionnaire d'erreurs de Symfony, après celui du pare-feu.
        return [KernelEvents::EXCEPTION => ['onKernelException', -10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        $previous = $exception->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            $event->setResponse($this->erreursValidation($previous->getViolations()));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $this->messageMetier($exception) ? $exception->getMessage() : match ($status) {
                404 => 'Élément introuvable.',
                403 => 'Vous n\'avez pas accès à cette action.',
                422 => 'Certaines informations sont invalides.',
                429 => 'Trop de demandes. Réessayez plus tard.',
                default => 'Requête invalide.',
            };
            $event->setResponse(new JsonResponse(['message' => $message], $status, $exception->getHeaders()));
        }
    }

    public function erreursValidation(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $champ = $violation->getPropertyPath() ?: '_';
            $errors[$champ] ??= $violation->getMessage();
        }

        return new JsonResponse(['message' => 'Certaines informations sont invalides.', 'errors' => $errors], 422);
    }

    /** Seuls les messages métier (écrits pour l'habitant) sont affichés tels quels. */
    private function messageMetier(\Throwable $e): bool
    {
        return $e instanceof ApiException;
    }
}
