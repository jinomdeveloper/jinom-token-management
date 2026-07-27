<?php

namespace Jinom\Keycloak;

use Jinom\Keycloak\Contracts\TokenManagerInterface;
use Jinom\Keycloak\Services\TokenManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class KeycloakSdkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('keycloak')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        // Register TokenManager as singleton
        $this->app->singleton(TokenManager::class, function ($app) {
            return new TokenManager;
        });

        // Register KeycloakAdminManager as singleton
        $this->app->singleton(\Jinom\Keycloak\Services\KeycloakAdminManager::class, function ($app) {
            return new \Jinom\Keycloak\Services\KeycloakAdminManager(
                $app->make(TokenManager::class)
            );
        });

        // Bind interface to implementation
        $this->app->bind(TokenManagerInterface::class, TokenManager::class);

        // Register main SDK class as singleton
        $this->app->singleton(KeycloakSdk::class, function ($app) {
            return new KeycloakSdk(
                $app->make(TokenManager::class),
                $app->make(\Jinom\Keycloak\Services\KeycloakAdminManager::class)
            );
        });

        // Alias for facade
        $this->app->alias(KeycloakSdk::class, 'keycloak-sdk');
    }

    public function packageBooted(): void
    {
        // Additional boot logic if needed
    }
}
