<?php

namespace Jinom\Keycloak\Exceptions;

use Exception;

class KeycloakAdminException extends Exception
{
    public function __construct(
        string $message = 'Keycloak Admin API request failed',
        int $code = 0,
        ?Exception $previous = null,
        public ?string $errorDescription = null,
        public ?array $responseBody = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function apiError(string $action, string $errorDescription, int $httpCode = 0, ?array $responseBody = null): self
    {
        return new self(
            message: "Keycloak Admin API failed to {$action}: {$errorDescription}",
            code: $httpCode,
            errorDescription: $errorDescription,
            responseBody: $responseBody
        );
    }

    public static function missingToken(): self
    {
        return new self(
            message: 'No valid admin token or client credentials token available to perform Keycloak Admin API operation.',
            code: 401
        );
    }
}
