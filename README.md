# Keycloak SDK for Laravel

Laravel package for Keycloak token management - store, refresh, introspect, and manage OAuth tokens. Supports both User tokens (Authorization Code Flow) and Client tokens (Client Credentials Flow).

## Installation

```bash
composer require jinom/keycloak-sdk
```

Publish the config file:

```bash
php artisan vendor:publish --tag="keycloak-config"
```

## Configuration

Add these environment variables to your `.env`:

```env
KEYCLOAK_BASE_URL=https://your-keycloak-server.com
KEYCLOAK_REALM=your-realm
KEYCLOAK_CLIENT_ID=your-client-id
KEYCLOAK_CLIENT_SECRET=your-client-secret

# Service Account (Client Credentials) - Optional
# Falls back to KEYCLOAK_CLIENT_ID/SECRET if not set
KEYCLOAK_SERVICE_ACCOUNT_ENABLED=true
KEYCLOAK_SERVICE_CLIENT_ID=your-service-client-id
KEYCLOAK_SERVICE_CLIENT_SECRET=your-service-client-secret

# Token Cache - Optional
KEYCLOAK_TOKEN_CACHE_PREFIX=keycloak_tokens
KEYCLOAK_TOKEN_CACHE_TTL=2592000
KEYCLOAK_TOKEN_BUFFER_SECONDS=30
KEYCLOAK_CLIENT_TOKEN_TTL=300
```

## Usage

### User Token (Authorization Code Flow)

For operations on behalf of a user:

```php
use Jinom\Keycloak\Facades\KeycloakSdk;

// Store tokens after OAuth callback
KeycloakSdk::storeTokens($userId, [
    'access_token' => $token,
    'refresh_token' => $refreshToken,
    'expires_in' => 300,
]);

// Get a valid token (auto-refreshes if expired)
$token = KeycloakSdk::getValidToken($userId);

// Check if user has valid tokens
if (KeycloakSdk::hasValidTokens($userId)) {
    // User is authenticated
}

// Get all token data
$tokenData = KeycloakSdk::getTokenData($userId);

// Introspect a token
$introspection = KeycloakSdk::introspectToken($token);

// Clear tokens (e.g., on logout)
KeycloakSdk::clearTokens($userId);
```

### Client Token (Client Credentials Flow)

For service-to-service communication without user context:

```php
use Jinom\Keycloak\Facades\KeycloakSdk;

// Get client token for system operations
$clientToken = KeycloakSdk::getClientToken();

// Use for API calls
Http::withToken($clientToken)->get('https://api.example.com/users');
```

### Using Dependency Injection

```php
use Jinom\Keycloak\Contracts\TokenManagerInterface;

class MyController extends Controller
{
    public function __construct(
        private TokenManagerInterface $tokenManager
    ) {}

    public function userAction(int $userId)
    {
        // User token
        $token = $this->tokenManager->getValidToken($userId);
    }

    public function systemAction()
    {
        // Client token
        $token = $this->tokenManager->getClientToken();
    }
}
```

## When to Use Which Token?

| Operation             | Token Type       | Reason                           |
| --------------------- | ---------------- | -------------------------------- |
| Check user exists     | **Client Token** | System checking, no user context |
| Create/Register user  | **Client Token** | System provisioning              |
| Update user by self   | **User Token**   | User changing own data           |
| Update user by system | **Client Token** | System/admin sync                |
| Delete user           | **Client Token** | Admin operation                  |
| Get own profile       | **User Token**   | User accessing own data          |
| List all users        | **Client Token** | Admin/system operation           |

## API Reference

| Method                                 | Description                                |
| -------------------------------------- | ------------------------------------------ |
| `storeTokens($userId, $tokenData)`     | Store tokens from OAuth callback           |
| `getValidToken($userId)`               | Get valid user access token (auto-refresh) |
| `getClientToken()`                     | Get client token (Client Credentials flow) |
| `refreshToken($userId, $refreshToken)` | Manually refresh the access token          |
| `clearTokens($userId)`                 | Clear all tokens for a user                |
| `hasValidTokens($userId)`              | Check if user has valid tokens             |
| `getTokenData($userId)`                | Get all stored token data                  |
| `introspectToken($token)`              | Introspect token with Keycloak server      |

## Keycloak Setup for Client Credentials

1. Go to Keycloak Admin Console
2. Select your realm
3. Go to **Clients** → Select your client
4. Enable **Service Account Enabled** under **Settings**
5. Add required **Service Account Roles** under **Service Account Roles** tab

## License

MIT
