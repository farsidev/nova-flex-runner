<?php

namespace Farsi\NovaFlexRunner;

use Farsi\NovaFlexRunner\Tools\FlexRunnerTool;
use Farsi\NovaFlexRunner\Tools\LogViewerTool;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class NovaFlexRunnerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadMigrations();
        $this->publishAssets();
        $this->registerPolicies();
        $this->registerNovaAssets();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nova-flex-runner.php', 'nova-flex-runner');
    }

    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function publishAssets(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/nova-flex-runner.php' => config_path('nova-flex-runner.php'),
            ], 'nova-flex-runner-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'nova-flex-runner-migrations');

            // Publish assets
            $this->publishes([
                __DIR__ . '/../dist' => public_path('vendor/nova-flex-runner'),
            ], 'nova-flex-runner-assets');
        }
    }

    protected function registerPolicies(): void
    {
        Gate::define('viewNovaFlexRunner', function ($user) {
            return $this->authorizeUser($user, 'view');
        });

        Gate::define('executeNovaFlexRunner', function ($user) {
            return $this->authorizeUser($user, 'execute');
        });

        Gate::define('viewNovaFlexRunnerLogs', function ($user) {
            return $this->authorizeUser($user, 'viewLogs');
        });
    }

    protected function authorizeUser($user, string $action): bool
    {
        $permissions = config('nova-flex-runner.permissions', []);
        
        if (empty($permissions)) {
            // Default behavior: allow all authenticated users
            return true;
        }

        // Check if user has specific permission
        if (isset($permissions[$action])) {
            $permission = $permissions[$action];
            
            if (is_callable($permission)) {
                return $permission($user);
            }
            
            if (is_string($permission) && method_exists($user, 'can')) {
                return $user->can($permission);
            }
            
            if (is_array($permission)) {
                // Check if user has any of the required roles/permissions
                foreach ($permission as $perm) {
                    if (method_exists($user, 'can') && $user->can($perm)) {
                        return true;
                    }
                    if (method_exists($user, 'hasRole') && $user->hasRole($perm)) {
                        return true;
                    }
                }
                return false;
            }
        }

        return true;
    }

    protected function registerNovaAssets(): void
    {
        Nova::serving(function (ServingNova $event) {
            Nova::tools([
                new FlexRunnerTool(),
                new LogViewerTool(),
            ]);
        });
    }
}