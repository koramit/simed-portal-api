<?php

namespace Koramit\SiMEDPortalAPI;

use Illuminate\Support\ServiceProvider;

class PortalAPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'simed-portal');
    }
}
