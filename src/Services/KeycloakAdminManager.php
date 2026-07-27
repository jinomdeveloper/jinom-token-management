<?php

namespace Jinom\Keycloak\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jinom\Keycloak\Exceptions\KeycloakAdminException;

class KeycloakAdminManager
{
    private string $adminBaseUrl;

    public function __construct(
        protected TokenManager $tokenManager
    ) {
        $baseUrl = config('keycloak.base_url');
        $realm = config('keycloak.realm');
        $this->adminBaseUrl = rtrim($baseUrl, '/')."/admin/realms/{$realm}";
    }

    /**
     * Get bearer token for admin operations.
     * Uses provided $adminToken or falls back to client credentials token.
     */
    protected function resolveAdminToken(?string $adminToken = null): string
    {
        $token = $adminToken ?? $this->tokenManager->getClientToken();

        if (empty($token)) {
            throw KeycloakAdminException::missingToken();
        }

        return $token;
    }

    /**
     * Create a new user account in Keycloak.
     *
     * @param array $userData User payload according to Keycloak Admin API UserRepresentation schema.
     * @param string|null $adminToken Optional admin token. If null, service account client token will be used.
     * @return string Created Keycloak User ID if available from Location header, or empty string.
     * @throws KeycloakAdminException
     */
    public function createUser(array $userData, ?string $adminToken = null): string
    {
        $token = $this->resolveAdminToken($adminToken);
        $url = "{$this->adminBaseUrl}/users";

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post($url, $userData);

            if (! $response->successful()) {
                $errorDescription = $response->json('errorMessage') 
                    ?? $response->json('error_description') 
                    ?? $response->body();

                Log::error('KeycloakSdk: User creation failed', [
                    'status' => $response->status(),
                    'error' => $errorDescription,
                ]);

                throw KeycloakAdminException::apiError('create user', $errorDescription, $response->status(), $response->json());
            }

            Log::info('KeycloakSdk: User created successfully');

            // Extract Keycloak User ID from Location header if available
            $location = $response->header('Location');
            if ($location) {
                $parts = explode('/', $location);
                return end($parts);
            }

            return '';
        } catch (KeycloakAdminException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('KeycloakSdk: Exception during user creation', ['error' => $e->getMessage()]);
            throw new KeycloakAdminException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Update user email in Keycloak.
     *
     * @param string $keycloakUserId Keycloak User UUID.
     * @param string $newEmail New email address.
     * @param bool $emailVerified Whether the email should be marked as verified.
     * @param string|null $adminToken Optional admin token.
     * @return bool
     * @throws KeycloakAdminException
     */
    public function updateUserEmail(string $keycloakUserId, string $newEmail, bool $emailVerified = false, ?string $adminToken = null): bool
    {
        $token = $this->resolveAdminToken($adminToken);
        $url = "{$this->adminBaseUrl}/users/{$keycloakUserId}";

        $payload = [
            'email' => $newEmail,
            'emailVerified' => $emailVerified,
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->put($url, $payload);

            if (! $response->successful()) {
                $errorDescription = $response->json('errorMessage') 
                    ?? $response->json('error_description') 
                    ?? $response->body();

                Log::error('KeycloakSdk: Update user email failed', [
                    'user_id' => $keycloakUserId,
                    'status' => $response->status(),
                    'error' => $errorDescription,
                ]);

                throw KeycloakAdminException::apiError('update email', $errorDescription, $response->status(), $response->json());
            }

            Log::info('KeycloakSdk: User email updated successfully', ['user_id' => $keycloakUserId]);

            return true;
        } catch (KeycloakAdminException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('KeycloakSdk: Exception during email update', ['user_id' => $keycloakUserId, 'error' => $e->getMessage()]);
            throw new KeycloakAdminException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Reset / change password for another user in Keycloak.
     *
     * @param string $keycloakUserId Keycloak User UUID.
     * @param string $newPassword New password string.
     * @param bool $temporary Whether user is forced to change password on next login.
     * @param string|null $adminToken Optional admin token.
     * @return bool
     * @throws KeycloakAdminException
     */
    public function updateUserPassword(string $keycloakUserId, string $newPassword, bool $temporary = false, ?string $adminToken = null): bool
    {
        $token = $this->resolveAdminToken($adminToken);
        $url = "{$this->adminBaseUrl}/users/{$keycloakUserId}/reset-password";

        $payload = [
            'type' => 'password',
            'value' => $newPassword,
            'temporary' => $temporary,
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->put($url, $payload);

            if (! $response->successful()) {
                $errorDescription = $response->json('errorMessage') 
                    ?? $response->json('error_description') 
                    ?? $response->body();

                Log::error('KeycloakSdk: Update user password failed', [
                    'user_id' => $keycloakUserId,
                    'status' => $response->status(),
                    'error' => $errorDescription,
                ]);

                throw KeycloakAdminException::apiError('reset password', $errorDescription, $response->status(), $response->json());
            }

            Log::info('KeycloakSdk: User password reset successfully', ['user_id' => $keycloakUserId]);

            return true;
        } catch (KeycloakAdminException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('KeycloakSdk: Exception during password reset', ['user_id' => $keycloakUserId, 'error' => $e->getMessage()]);
            throw new KeycloakAdminException($e->getMessage(), 0, $e);
        }
    }
}
