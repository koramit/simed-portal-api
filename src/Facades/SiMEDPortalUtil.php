<?php

namespace Koramit\SiMEDPortalAPI\Facades;

use Illuminate\Support\Facades\Facade;
use Koramit\SiMEDPortalAPI\DTOs\PortalResultDto;
use Koramit\SiMEDPortalAPI\DTOs\RecentlyEncounterDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitBundleDto;
use Koramit\SiMEDPortalAPI\PortalUtilManager;

/**
 * @method static RecentlyEncounterDto getPatientRecentlyEncounter(int $hn)
 * @method static VisitBundleDto getVisitBundle(int $vn)
 * @method static PortalResultDto getPatientWS(string|int $hn, bool $withSensitiveData = true)
 * @method static PortalResultDto getPatientDSL(string $keyValue, string $keyName = 'hn', bool $withSensitiveData = true)
 * @method static PortalResultDto getUser(string|int $id)
 * @method static PortalResultDto authenticate(string $login, string $password)
 * @method static PortalResultDto getAdmissionWS(string|int $an, bool $withSensitiveData = true)
 * @method static PortalResultDto getAdmissionDSL(string|int $an)
 * @method static PortalResultDto getWardActiveAdmissions(int $wardNumber)
 * @method static PortalResultDto getActiveAdmissionWards()
 * @method static PortalResultDto getDischargedAdmissions(string $beginDate, ?string $endDate = null, ?int $wardNumber = null)
 *
 * @see PortalUtilManager
 */
class SiMEDPortalUtil extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'simed-portal-util';
    }
}
