<?php

namespace Koramit\SiMEDPortalAPI\Facades;

use Illuminate\Support\Facades\Facade;

class SiMEDPortalUtil extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'simed-portal-util';
    }
}
