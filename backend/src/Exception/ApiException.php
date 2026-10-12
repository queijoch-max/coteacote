<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Erreur métier dont le message (en français, sans jargon) est affiché tel quel à l'habitant.
 */
class ApiException extends HttpException
{
    public function __construct(string $message, int $statusCode = 400, array $headers = [])
    {
        parent::__construct($statusCode, $message, null, $headers);
    }
}
