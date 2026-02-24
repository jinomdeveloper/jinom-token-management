<?php

namespace Jinom\Keycloak\Exceptions;

use Exception;

class TokenRefreshException extends Exception
{
    public function __construct(
        string $message = 'Failed to refresh Keycloak token',
        int $code = 0,
        ?Exception $previous = null,
        public int|string|null $userId = null,
        public ?string $errorDescription = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for expired refresh token
     */
    public static function refreshTokenExpired(int|string $userId): self
    {
        return new self(
            message: 'Refresh token has expired. User must re-authenticate.',
            code: 401,
            userId: $userId
        );
    }

    /**
     * Create exception for missing refresh token
     */
    public static function noRefreshToken(int|string $userId): self
    {
        return new self(
            message: 'No refresh token available for user.',
            code: 400,
            userId: $userId
        );
    }

    /**
     * Create exception for Keycloak API error
     */
    public static function keycloakError(int|string $userId, string $errorDescription, int $httpCode = 0): self
    {
        return new self(
            message: "Keycloak token refresh failed: {$errorDescription}",
            code: $httpCode,
            userId: $userId,
            errorDescription: $errorDescription
        );
    }
}
