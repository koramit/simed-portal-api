<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Utils\RecentlyEncounter;
use Koramit\SiMEDPortalAPI\Utils\VisitBundle;

class PortalUtilManager
{
    public function getPatientRecentlyEncounter(int $hn)
    {
        return app(RecentlyEncounter::class)($hn);
    }

    public function getVisitBundle(int $vn)
    {
        return app(VisitBundle::class)($vn);
    }
}
