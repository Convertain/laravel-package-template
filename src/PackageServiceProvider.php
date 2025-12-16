<?php

declare(strict_types=1);

namespace Vendor\Package;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerMigrations();
        $this->registerPublishing();
    }

    protected function registerConfig(): void
    {
        if (file_exists(__DIR__.'/../config/package-template.php')) {
            $this->mergeConfigFrom(
                __DIR__.'/../config/package-template.php',
                'package-template',
            );
        }
    }

    protected function registerRoutes(): void
    {
        if (file_exists(__DIR__.'/../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (file_exists(__DIR__.'/../routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }

    protected function registerViews(): void
    {
        if (is_dir(__DIR__.'/../resources/views')) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'package-template');
        }
    }

    protected function registerTranslations(): void
    {
        if (is_dir(__DIR__.'/../lang')) {
            $this->loadTranslationsFrom(__DIR__.'/../lang', 'package-template');
            $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
        }
    }

    protected function registerMigrations(): void
    {
        if (is_dir(__DIR__.'/../database/migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (file_exists(__DIR__.'/../config/package-template.php')) {
            $this->publishes([
                __DIR__.'/../config/package-template.php' => config_path('package-template.php'),
            ], 'package-template-config');
        }

        if (is_dir(__DIR__.'/../resources/views')) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/package-template'),
            ], 'package-template-views');
        }

        if (is_dir(__DIR__.'/../lang')) {
            $targetLangPath = function_exists('lang_path')
                ? call_user_func('lang_path', 'vendor/package-template')
                : resource_path('lang/vendor/package-template');

            $this->publishes([
                __DIR__.'/../lang' => $targetLangPath,
            ], 'package-template-lang');
        }

        if (is_dir(__DIR__.'/../database/migrations')) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'package-template-migrations');
        }
    }
}
