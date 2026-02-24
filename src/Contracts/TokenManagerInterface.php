<?php

namespace Jinom\Keycloak\Contracts;

interface TokenManagerInterface
{
    /**
     * Store tokens from Keycloak callback
     */
    public function storeTokens(int|string $userId, array $tokenData): void;

    /**
     * Get a valid access token (auto-refresh if expired)
     */
    public function getValidToken(int|string $userId): ?string;

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(int|string $userId, string $refreshToken): ?string;

    /**
     * Clear all tokens for a user
     */
    public function clearTokens(int|string $userId): void;

    /**
     * Get all token data for a user
     */
    public function getTokenData(int|string $userId): ?array;

    /**
     * Check if user has valid tokens
     */
    public function hasValidTokens(int|string $userId): bool;

    /**
     * Introspect token to verify it's still valid with Keycloak
     */
    public function introspectToken(string $token): ?array;

    /**
     * Get client token using Client Credentials flow (service-to-service)
     * This token represents the application, not a user.
     */
    public function getClientToken(): ?string;
}

