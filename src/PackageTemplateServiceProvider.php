<?php

declare(strict_types=1);

namespace Convertain\PackageTemplate;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PackageTemplateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->bootConfig();
        $this->bootMigrations();
        $this->bootPublishing();
        $this->bootRoutes();
        $this->bootViews();
        $this->bootTranslations();
        $this->bootCommands();
        $this->bootIntegrations();
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/package-template.php',
            'package-template',
        );
    }

    /**
     * Boot package configuration.
     */
    protected function bootConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/package-template.php' => config_path('package-template.php'),
            ], 'package-template-config');
        }
    }

    /**
     * Boot package migrations.
     */
    protected function bootMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'package-template-migrations');
        }
    }

    /**
     * Boot package publishing.
     */
    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/package-template'),
            ], 'package-template-views');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/package-template'),
            ], 'package-template-translations');
        }
    }

    /**
     * Boot package routes.
     */
    protected function bootRoutes(): void
    {
        if (file_exists(__DIR__.'/../routes/web.php')) {
            Route::middleware('web')
                ->group(__DIR__.'/../routes/web.php');
        }

        if (file_exists(__DIR__.'/../routes/api.php')) {
            Route::prefix('api')
                ->middleware('api')
                ->group(__DIR__.'/../routes/api.php');
        }
    }

    /**
     * Boot package views.
     */
    protected function bootViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'package-template');
    }

    /**
     * Boot package translations.
     */
    protected function bootTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'package-template');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
    }

    /**
     * Boot package commands.
     */
    protected function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Register commands here
            ]);
        }
    }

    /**
     * Boot integrations with other packages.
     */
    protected function bootIntegrations(): void
    {
        // Detect and integrate with other packages
        $this->bootOrganizationsIntegration();
        $this->bootPermissionsIntegration();
        $this->bootCheckoutIntegration();
    }

    /**
     * Integrate with Organizations package if present.
     */
    protected function bootOrganizationsIntegration(): void
    {
        if (class_exists('Convertain\Organizations\OrganizationsServiceProvider')) {
            // Register organization-specific features
            $this->app->booted(function () {
                // Add organization-scoped functionality
            });
        }
    }

    /**
     * Integrate with Permissions package if present.
     */
    protected function bootPermissionsIntegration(): void
    {
        if (class_exists('Convertain\Permissions\PermissionsServiceProvider')) {
            // Register package permissions
            $this->app->booted(function () {
                // Register default permissions for this package
            });
        }
    }

    /**
     * Integrate with Checkout package if present.
     */
    protected function bootCheckoutIntegration(): void
    {
        if (class_exists('Convertain\Checkout\CheckoutServiceProvider')) {
            // Register checkout-specific features
            $this->app->booted(function () {
                // Add billing-related functionality
            });
        }
    }
}
