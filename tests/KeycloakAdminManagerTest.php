<?php

use Illuminate\Support\Facades\Http;
use Jinom\Keycloak\Exceptions\KeycloakAdminException;
use Jinom\Keycloak\Facades\KeycloakSdk;

beforeEach(function () {
    config()->set('keycloak.base_url', 'https://keycloak.example.com');
    config()->set('keycloak.realm', 'myrealm');
    config()->set('keycloak.client_id', 'test-client');
    config()->set('keycloak.client_secret', 'test-secret');
});

test('it can create user with admin token', function () {
    Http::fake([
        'https://keycloak.example.com/admin/realms/myrealm/users' => Http::response(null, 201, [
            'Location' => 'https://keycloak.example.com/admin/realms/myrealm/users/user-uuid-12345',
        ]),
    ]);

    $userId = KeycloakSdk::createUser([
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'enabled' => true,
    ], 'super-admin-token-123');

    expect($userId)->toBe('user-uuid-12345');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://keycloak.example.com/admin/realms/myrealm/users' &&
            $request->hasHeader('Authorization', 'Bearer super-admin-token-123') &&
            $request['username'] === 'johndoe';
    });
});

test('it can update user email with admin token', function () {
    Http::fake([
        'https://keycloak.example.com/admin/realms/myrealm/users/user-uuid-12345' => Http::response(null, 204),
    ]);

    $result = KeycloakSdk::updateUserEmail('user-uuid-12345', 'newemail@example.com', true, 'super-admin-token-123');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://keycloak.example.com/admin/realms/myrealm/users/user-uuid-12345' &&
            $request->hasHeader('Authorization', 'Bearer super-admin-token-123') &&
            $request['email'] === 'newemail@example.com' &&
            $request['emailVerified'] === true;
    });
});

test('it can reset user password with admin token', function () {
    Http::fake([
        'https://keycloak.example.com/admin/realms/myrealm/users/user-uuid-12345/reset-password' => Http::response(null, 204),
    ]);

    $result = KeycloakSdk::updateUserPassword('user-uuid-12345', 'newSecretPassword123!', false, 'super-admin-token-123');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://keycloak.example.com/admin/realms/myrealm/users/user-uuid-12345/reset-password' &&
            $request->hasHeader('Authorization', 'Bearer super-admin-token-123') &&
            $request['type'] === 'password' &&
            $request['value'] === 'newSecretPassword123!' &&
            $request['temporary'] === false;
    });
});

test('it throws KeycloakAdminException on API error', function () {
    Http::fake([
        'https://keycloak.example.com/admin/realms/myrealm/users/user-uuid-12345/reset-password' => Http::response([
            'errorMessage' => 'User not found',
        ], 404),
    ]);

    KeycloakSdk::updateUserPassword('user-uuid-12345', 'newSecretPassword123!', false, 'super-admin-token-123');
})->throws(KeycloakAdminException::class);
