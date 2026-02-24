<?php

namespace Jinom\Keycloak\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jinom\Keycloak\Contracts\TokenManagerInterface;
use Jinom\Keycloak\Exceptions\TokenRefreshException;

class TokenManager implements TokenManagerInterface
{
    private string $tokenUrl;

    private string $clientId;

    private string $clientSecret;

    private string $cachePrefix;

    private int $cacheTtl;

    private int $bufferSeconds;

    public function __construct()
    {
        $baseUrl = config('keycloak.base_url');
        $realm = config('keycloak.realm');

        $this->tokenUrl = rtrim($baseUrl, '/')."/realms/{$realm}/protocol/openid-connect/token";
        $this->clientId = config('keycloak.client_id');
        $this->clientSecret = config('keycloak.client_secret');
        $this->cachePrefix = config('keycloak.token.cache_prefix', 'keycloak_tokens');
        $this->cacheTtl = config('keycloak.token.cache_ttl', 60 * 60 * 24 * 30);
        $this->bufferSeconds = config('keycloak.token.buffer_seconds', 30);
    }

    /**
     * Store tokens from Keycloak callback
     */
    public function storeTokens(int|string $userId, array $tokenData): void
    {
        $expiresIn = $tokenData['expires_in'] ?? $tokenData['expiresIn'] ?? 300;

        $data = [
            'access_token' => $tokenData['access_token'] ?? $tokenData['token'] ?? null,
            'refresh_token' => $tokenData['refresh_token'] ?? $tokenData['refreshToken'] ?? null,
            'id_token' => $tokenData['id_token'] ?? null,
            'expires_at' => now()->addSeconds($expiresIn - $this->bufferSeconds)->timestamp,
            'keycloak_id' => $tokenData['keycloak_id'] ?? $tokenData['sub'] ?? null,
        ];

        Cache::put(
            $this->getCacheKey($userId),
            $data,
            now()->addSeconds($this->cacheTtl)
        );

        Log::debug('KeycloakSdk: Tokens stored', [
            'user_id' => $userId,
            'expires_at' => $data['expires_at'],
        ]);
    }

    /**
     * Get a valid access token (auto-refresh if expired)
     */
    public function getValidToken(int|string $userId): ?string
    {
        $tokenData = $this->getTokenData($userId);

        if (! $tokenData) {
            Log::warning('KeycloakSdk: No tokens found', ['user_id' => $userId]);

            return null;
        }

        // Check if token is expired or about to expire
        if ($this->isTokenExpired($tokenData['expires_at'])) {
            Log::info('KeycloakSdk: Token expired, attempting refresh', ['user_id' => $userId]);

            if (empty($tokenData['refresh_token'])) {
                Log::error('KeycloakSdk: No refresh token available', ['user_id' => $userId]);

                return null;
            }

            return $this->refreshToken($userId, $tokenData['refresh_token']);
        }

        return $tokenData['access_token'];
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(int|string $userId, string $refreshToken): ?string
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->tokenUrl, [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                ]);

            if (! $response->successful()) {
                $errorDescription = $response->json('error_description') ?? $response->body();

                Log::error('KeycloakSdk: Token refresh failed', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'error' => $errorDescription,
                ]);

                // Clear invalid tokens
                $this->clearTokens($userId);

                throw TokenRefreshException::keycloakError($userId, $errorDescription, $response->status());
            }

            $data = $response->json();

            // Store the new tokens
            $this->storeTokens($userId, [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                'id_token' => $data['id_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 300,
                'keycloak_id' => $this->getTokenData($userId)['keycloak_id'] ?? null,
            ]);

            Log::info('KeycloakSdk: Token refreshed successfully', ['user_id' => $userId]);

            return $data['access_token'];

        } catch (TokenRefreshException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('KeycloakSdk: Token refresh exception', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            $this->clearTokens($userId);

            return null;
        }
    }

    /**
     * Clear all tokens for a user
     */
    public function clearTokens(int|string $userId): void
    {
        Cache::forget($this->getCacheKey($userId));

        Log::debug('KeycloakSdk: Tokens cleared', ['user_id' => $userId]);
    }

    /**
     * Get all token data for a user
     */
    public function getTokenData(int|string $userId): ?array
    {
        return Cache::get($this->getCacheKey($userId));
    }

    /**
     * Check if user has valid tokens
     */
    public function hasValidTokens(int|string $userId): bool
    {
        $tokenData = $this->getTokenData($userId);

        if (! $tokenData) {
            return false;
        }

        // If access token expired, check if refresh token is available
        if ($this->isTokenExpired($tokenData['expires_at'])) {
            return ! empty($tokenData['refresh_token']);
        }

        return true;
    }

    /**
     * Check if token is expired
     */
    private function isTokenExpired(int $expiresAt): bool
    {
        return now()->timestamp >= $expiresAt;
    }

    /**
     * Get cache key for user tokens
     */
    private function getCacheKey(int|string $userId): string
    {
        return "{$this->cachePrefix}:{$userId}";
    }

    /**
     * Introspect token to verify it's still valid with Keycloak
     */
    public function introspectToken(string $token): ?array
    {
        try {
            $baseUrl = config('keycloak.base_url');
            $realm = config('keycloak.realm');
            $introspectUrl = rtrim($baseUrl, '/')."/realms/{$realm}/protocol/openid-connect/token/introspect";

            $response = Http::asForm()
                ->timeout(30)
                ->post($introspectUrl, [
                    'token' => $token,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['active'] ? $data : null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('KeycloakSdk: Token introspection failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get client token using Client Credentials flow (service-to-service)
     * This token represents the application, not a user.
     */
    public function getClientToken(): ?string
    {
        if (! config('keycloak.service_account.enabled', true)) {
            Log::warning('KeycloakSdk: Service account is disabled');

            return null;
        }

        $cacheKey = "{$this->cachePrefix}:client_credentials";
        $cached = Cache::get($cacheKey);

        // Return cached token if still valid
        if ($cached && ! $this->isTokenExpired($cached['expires_at'])) {
            return $cached['access_token'];
        }

        try {
            $clientId = config('keycloak.service_account.client_id', $this->clientId);
            $clientSecret = config('keycloak.service_account.client_secret', $this->clientSecret);

            $response = Http::asForm()
                ->timeout(30)
                ->post($this->tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if (! $response->successful()) {
                $errorDescription = $response->json('error_description') ?? $response->body();

                Log::error('KeycloakSdk: Client credentials token request failed', [
                    'status' => $response->status(),
                    'error' => $errorDescription,
                ]);

                return null;
            }

            $data = $response->json();
            $expiresIn = $data['expires_in'] ?? 300;
            $clientTokenTtl = config('keycloak.token.client_token_ttl', 60 * 5);

            // Cache the client token
            Cache::put($cacheKey, [
                'access_token' => $data['access_token'],
                'expires_at' => now()->addSeconds($expiresIn - $this->bufferSeconds)->timestamp,
            ], now()->addSeconds(min($expiresIn, $clientTokenTtl)));

            Log::debug('KeycloakSdk: Client token obtained successfully');

            return $data['access_token'];

        } catch (\Exception $e) {
            Log::error('KeycloakSdk: Client credentials token exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
