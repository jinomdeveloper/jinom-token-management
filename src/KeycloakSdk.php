<?php

namespace Jinom\Keycloak;

use Jinom\Keycloak\Services\TokenManager;

/**
 * Main entry point for Keycloak SDK
 *
 * Provides a unified interface for Keycloak token management:
 * - Store tokens from OAuth callback
 * - Auto-refresh expired tokens
 * - Introspect tokens
 * - Clear tokens on logout
 */
class KeycloakSdk
{
    public function __construct(
        protected TokenManager $tokenManager
    ) {}

    /**
     * Store tokens from Keycloak callback
     */
    public function storeTokens(int|string $userId, array $tokenData): void
    {
        $this->tokenManager->storeTokens($userId, $tokenData);
    }

    /**
     * Get a valid access token (auto-refreshes if expired)
     */
    public function getValidToken(int|string $userId): ?string
    {
        return $this->tokenManager->getValidToken($userId);
    }

    /**
     * Refresh the access token
     */
    public function refreshToken(int|string $userId, string $refreshToken): ?string
    {
        return $this->tokenManager->refreshToken($userId, $refreshToken);
    }

    /**
     * Clear all tokens for a user
     */
    public function clearTokens(int|string $userId): void
    {
        $this->tokenManager->clearTokens($userId);
    }

    /**
     * Check if user has valid tokens
     */
    public function hasValidTokens(int|string $userId): bool
    {
        return $this->tokenManager->hasValidTokens($userId);
    }

    /**
     * Get all token data for a user
     */
    public function getTokenData(int|string $userId): ?array
    {
        return $this->tokenManager->getTokenData($userId);
    }

    /**
     * Introspect token with Keycloak
     */
    public function introspectToken(string $token): ?array
    {
        return $this->tokenManager->introspectToken($token);
    }

    /**
     * Get client token using Client Credentials flow (service-to-service)
     * This token represents the application, not a user.
     */
    public function getClientToken(): ?string
    {
        return $this->tokenManager->getClientToken();
    }

    /**
     * Get the underlying TokenManager instance
     */
    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }
}
