<?php

namespace Jinom\Keycloak\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void storeTokens(int|string $userId, array $tokenData)
 * @method static string|null getValidToken(int|string $userId)
 * @method static string|null refreshToken(int|string $userId, string $refreshToken)
 * @method static void clearTokens(int|string $userId)
 * @method static bool hasValidTokens(int|string $userId)
 * @method static array|null getTokenData(int|string $userId)
 * @method static array|null introspectToken(string $token)
 * @method static string|null getClientToken()
 * @method static \Jinom\Keycloak\Services\TokenManager getTokenManager()
 *
 * @see \Jinom\Keycloak\KeycloakSdk
 */
class KeycloakSdk extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jinom\Keycloak\KeycloakSdk::class;
    }
}
