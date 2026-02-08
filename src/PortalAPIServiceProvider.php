<?php

namespace Koramit\SiMEDPortalAPI;

use Illuminate\Support\ServiceProvider;

class PortalAPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__.'/config.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom(__DIR__.'/config.php', 'simed-portal');
        }
    }
}
