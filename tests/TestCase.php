<?php

namespace Koramit\SiMEDPortalAPI\Tests;

use Koramit\SiMEDPortalAPI\PortalAPIServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public const ENDPOINT = 'https://portal.test/api';

    protected function getPackageProviders($app): array
    {
        return [PortalAPIServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.timezone', 'UTC');
        $app['config']->set('simed-portal.endpoint', self::ENDPOINT);
        $app['config']->set('simed-portal.token', 'test-token');
        $app['config']->set('simed-portal.retry_delay_ms', 0);
    }
}
