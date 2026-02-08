<?php

namespace Koramit\SiMEDPortalAPI;

use Illuminate\Support\ServiceProvider;

class PortalAPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'simed-portal');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => config_path('simed-portal.php'),
            ], 'simed-portal-config');
        }
    }

    protected function configPath(): string
    {
        return __DIR__.'/../config/simed-portal.php';
    }
}
