<?php

namespace Koramit\SiMEDPortalAPI\Facades;

use Illuminate\Support\Facades\Facade;
use Koramit\SiMEDPortalAPI\DTOs\LatestEncountersDto;
use Koramit\SiMEDPortalAPI\DTOs\PortalResultDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitBundleDto;
use Koramit\SiMEDPortalAPI\PortalUtilManager;

/**
 * @method static LatestEncountersDto getPatientLatestEncounters(string|int $hn)
 * @method static VisitBundleDto getVisitBundle(string|int $vn)
 * @method static PortalResultDto getPatientWS(string|int $hn, bool $withSensitiveData = true)
 * @method static PortalResultDto getPatientDSL(string $keyValue, string $keyName = 'hn', bool $withSensitiveData = true)
 * @method static PortalResultDto getUser(string|int $id)
 * @method static PortalResultDto authenticate(string $login, string $password)
 * @method static PortalResultDto getAdmissionWS(string|int $an, bool $withSensitiveData = true)
 * @method static PortalResultDto getAdmissionDSL(string|int $an)
 * @method static PortalResultDto getWardActiveAdmissions(string|int $wardNumber)
 * @method static PortalResultDto getActiveAdmissionWards()
 * @method static PortalResultDto getDischargedAdmissions(string|int $wardNumber, string $beginDate, ?string $endDate = null)
 * @method static PortalResultDto getDischargedAdmissionWards(string $beginDate, ?string $endDate = null)
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
