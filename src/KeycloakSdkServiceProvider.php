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

        // Bind interface to implementation
        $this->app->bind(TokenManagerInterface::class, TokenManager::class);

        // Register main SDK class as singleton
        $this->app->singleton(KeycloakSdk::class, function ($app) {
            return new KeycloakSdk(
                $app->make(TokenManager::class)
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
