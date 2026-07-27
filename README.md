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

### Keycloak Admin Operations

You can manage user accounts directly in Keycloak (Create User, Update Email, and Change/Reset Password).

> **Token Note:** If `$adminToken` parameter is omitted or set to `null`, the SDK automatically uses the Service Account token (`getClientToken()`). Alternatively, you can pass a Super Admin Bearer Token explicitly.

#### 1. Create User / Account

```php
use Jinom\Keycloak\Facades\KeycloakSdk;

$keycloakUserId = KeycloakSdk::createUser([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'firstName' => 'John',
    'lastName' => 'Doe',
    'enabled' => true,
    'credentials' => [
        [
            'type' => 'password',
            'value' => 'SecretPassword123!',
            'temporary' => false,
        ]
    ]
], adminToken: $superAdminToken); // $superAdminToken is optional
```

#### 2. Update User Email

```php
use Jinom\Keycloak\Facades\KeycloakSdk;

KeycloakSdk::updateUserEmail(
    keycloakUserId: 'uuid-user-target',
    newEmail: 'newemail@example.com',
    emailVerified: true,
    adminToken: $superAdminToken // optional
);
```

#### 3. Change / Reset User Password

```php
use Jinom\Keycloak\Facades\KeycloakSdk;

KeycloakSdk::updateUserPassword(
    keycloakUserId: 'uuid-user-target',
    newPassword: 'NewSecurePassword123!',
    temporary: false, // set true to force user to change password on next login
    adminToken: $superAdminToken // optional
);
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
| Reset user password   | **Admin Token / Client Token** | Admin operation    |
| Delete user           | **Client Token** | Admin operation                  |
| Get own profile       | **User Token**   | User accessing own data          |
| List all users        | **Client Token** | Admin/system operation           |

## API Reference

| Method                                                        | Description                                      |
| ------------------------------------------------------------- | ------------------------------------------------ |
| `storeTokens($userId, $tokenData)`                            | Store tokens from OAuth callback                 |
| `getValidToken($userId)`                                      | Get valid user access token (auto-refresh)       |
| `getClientToken()`                                            | Get client token (Client Credentials flow)       |
| `createUser($userData, $adminToken = null)`                   | Create a new user in Keycloak                    |
| `updateUserEmail($keycloakUserId, $email, $verified, $token)` | Update user email in Keycloak                    |
| `updateUserPassword($keycloakUserId, $pass, $temp, $token)`   | Reset/Change password for another user           |
| `refreshToken($userId, $refreshToken)`                        | Manually refresh the access token                |
| `clearTokens($userId)`                                        | Clear all tokens for a user                      |
| `hasValidTokens($userId)`                                     | Check if user has valid tokens                   |
| `getTokenData($userId)`                                       | Get all stored token data                        |
| `introspectToken($token)`                                     | Introspect token with Keycloak server            |

## Keycloak Setup for Service Account / Admin Operations

1. Go to Keycloak Admin Console
2. Select your realm
3. Go to **Clients** → Select your client (e.g., `jinom-panel`)
4. Enable **Client Authentication** and **Service Accounts Roles** under **Settings**
5. Go to **Service Accounts Roles** tab → Click **Assign role**
6. Change filter to **Filter by clients** → Select **realm-management**
7. Assign roles: `manage-users`, `query-users`, or `realm-admin`

## License

MIT

