<?php

use Jinom\Keycloak\Facades\KeycloakSdk;

it('tests creating user, updating email, and updating password via KeycloakAdminManager', function () {
    // 1. Mock HTTP Requests ke Keycloak Server
    Illuminate\Support\Facades\Http::fake([
        // Mock get client token (service account)
        '*/protocol/openid-connect/token' => Illuminate\Support\Facades\Http::response([
            'access_token' => 'mocked-service-account-token',
            'expires_in' => 300,
        ], 200),

        // Mock create user endpoint
        '*/admin/realms/*/users' => Illuminate\Support\Facades\Http::response(null, 201, [
            'Location' => 'https://keycloak.example.com/admin/realms/myrealm/users/generated-user-uuid-999',
        ]),

        // Mock update email endpoint
        '*/admin/realms/*/users/generated-user-uuid-999' => Illuminate\Support\Facades\Http::response(null, 204),

        // Mock update/reset password endpoint
        '*/admin/realms/*/users/generated-user-uuid-999/reset-password' => Illuminate\Support\Facades\Http::response(null, 204),
    ]);

    // Config setup
    config()->set('keycloak.base_url', 'https://keycloak.example.com');
    config()->set('keycloak.realm', 'myrealm');
    config()->set('keycloak.client_id', 'my-client');
    config()->set('keycloak.client_secret', 'my-secret');

    // A. TEST CREATE USER (Menggunakan Service Account Client Token secara otomatis)
    $createdUserId = KeycloakSdk::createUser([
        'username' => 'testuser',
        'email' => 'testuser@example.com',
        'firstName' => 'Test',
        'lastName' => 'User',
        'enabled' => true,
    ]);

    expect($createdUserId)->toBe('generated-user-uuid-999');

    // B. TEST UPDATE EMAIL (Menggunakan Super Admin Token manual)
    $superAdminToken = 'custom-super-admin-bearer-token';
    $emailUpdated = KeycloakSdk::updateUserEmail(
        keycloakUserId: $createdUserId,
        newEmail: 'updated_email@example.com',
        emailVerified: true,
        adminToken: $superAdminToken
    );

    expect($emailUpdated)->toBeTrue();

    // C. TEST UPDATE PASSWORD (Menggunakan Super Admin Token manual)
    $passwordUpdated = KeycloakSdk::updateUserPassword(
        keycloakUserId: $createdUserId,
        newPassword: 'NewSecretPassword123!',
        temporary: false,
        adminToken: $superAdminToken
    );

    expect($passwordUpdated)->toBeTrue();
});
