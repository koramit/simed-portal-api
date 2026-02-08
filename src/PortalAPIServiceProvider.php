<?php

namespace Koramit\SiMEDPortalAPI;

use Illuminate\Support\ServiceProvider;

class PortalAPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = $this->configPath();

        if ($configPath && file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'simed-portal');
        }
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $configPath = $this->configPath();

        if ($configPath && file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('simed-portal.php'),
            ], 'simed-portal-config');
        }
    }

    protected function configPath(): string|false
    {
        $path = __DIR__.'/../config/simed-portal.php';

        return realpath($path) ?: false;
    }
}
