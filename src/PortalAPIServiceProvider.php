<?php

namespace Koramit\SiMEDPortalAPI;

use Illuminate\Support\ServiceProvider;

class PortalAPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__ . '/../config/simed-portal.php';
        $this->mergeConfigFrom($configPath, 'simed-portal');

        if ($configPath && file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'simed-portal');
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $configPath = __DIR__ . '/../config/simed-portal.php';
            $this->publishes([$configPath => $this->getConfigPath()], 'config');
        }
    }

    /**
     * Get the config path
     *
     */
    protected function getConfigPath(): string
    {
        return config_path('simed-portal.php');
    }
}
