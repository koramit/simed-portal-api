<?php

namespace Koramit\SiMEDPortalAPI;

use Illuminate\Support\ServiceProvider;

class PortalAPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/simed-portal.php', 'simed-portal');

        $this->app->singleton('simed-portal-util', function ($app) {
            return new PortalUtilManager;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $configPath = __DIR__.'/../config/simed-portal.php';
            $this->publishes([$configPath => $this->getConfigPath()], 'config');
        }
    }

    /**
     * Get the config path
     */
    protected function getConfigPath(): string
    {
        return config_path('simed-portal.php');
    }
}
