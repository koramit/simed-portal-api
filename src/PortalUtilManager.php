<?php

namespace Koramit\SiMEDPortalAPI;

use Illuminate\Support\Collection;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionDto;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionSummaryDto;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionWardSummaryDto;
use Koramit\SiMEDPortalAPI\DTOs\ADUserDto;
use Koramit\SiMEDPortalAPI\DTOs\LatestEncountersDto;
use Koramit\SiMEDPortalAPI\DTOs\PatientDto;
use Koramit\SiMEDPortalAPI\DTOs\PortalResultDto;
use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitBundleDto;
use Koramit\SiMEDPortalAPI\Utils\LatestEncounters;
use Koramit\SiMEDPortalAPI\Utils\VisitBundle;

/**
 * Typed surface for host apps (behind the SiMEDPortalUtil facade).
 * The *API classes stay low-level and keep returning ResponseDto for older apps.
 * `$withSensitiveData` defaults to true, like the *API classes (see PatientAPI).
 */
class PortalUtilManager
{
    public function getPatientLatestEncounters(string|int $hn): LatestEncountersDto
    {
        return app(LatestEncounters::class)($hn);
    }

    public function getVisitBundle(string|int $vn): VisitBundleDto
    {
        return app(VisitBundle::class)($vn);
    }

    /** @return PortalResultDto<PatientDto> */
    public function getPatientWS(string|int $hn, bool $withSensitiveData = true): PortalResultDto
    {
        return $this->toResult(
            (new PatientAPI)->getPatientWS($hn, $withSensitiveData),
            fn (array $data) => PatientDto::fromWS($data),
        );
    }

    /** @return PortalResultDto<PatientDto> */
    public function getPatientDSL(string $keyValue, string $keyName = 'hn', bool $withSensitiveData = true): PortalResultDto
    {
        return $this->toResult(
            (new PatientAPI)->getPatientDSL($keyValue, $keyName, raw: false, withSensitiveData: $withSensitiveData),
            fn (array $data) => PatientDto::fromDSL($data),
        );
    }

    /** @return PortalResultDto<ADUserDto> */
    public function getUser(string|int $id): PortalResultDto
    {
        return $this->toResult(
            (new ADUserAPI)->getUser($id),
            fn (array $data) => ADUserDto::fromPortal($data),
        );
    }

    /**
     * Host apps must validate `login` / `password` first: a Portal 422 throws ValidationException.
     *
     * @return PortalResultDto<ADUserDto>
     */
    public function authenticate(string $login, string $password): PortalResultDto
    {
        return $this->toResult(
            (new ADUserAPI)->authenticate($login, $password),
            fn (array $data) => ADUserDto::fromPortal($data),
        );
    }

    /** @return PortalResultDto<AdmissionDto> */
    public function getAdmissionWS(string|int $an, bool $withSensitiveData = true): PortalResultDto
    {
        return $this->toResult(
            (new AdmissionAPI)->getAdmissionWS($an, $withSensitiveData),
            fn (array $data) => AdmissionDto::fromWS($data),
        );
    }

    /** @return PortalResultDto<AdmissionDto> */
    public function getAdmissionDSL(string|int $an): PortalResultDto
    {
        return $this->toResult(
            (new AdmissionAPI)->getAdmissionDSL($an),
            fn (array $data) => AdmissionDto::fromDSL($data),
        );
    }

    /** @return PortalResultDto<Collection<int, AdmissionSummaryDto>> */
    public function getWardActiveAdmissions(string|int $wardNumber): PortalResultDto
    {
        return $this->toListResult(
            (new AdmissionWardAPI)->getActiveAdmissions($wardNumber),
            fn (array $data) => AdmissionSummaryDto::fromPortal($data),
        );
    }

    /** @return PortalResultDto<Collection<int, AdmissionWardSummaryDto>> */
    public function getActiveAdmissionWards(): PortalResultDto
    {
        return $this->toListResult(
            (new AdmissionWardAPI)->getActiveAdmissions(),
            fn (array $data) => AdmissionWardSummaryDto::fromPortal($data),
        );
    }

    /** @return PortalResultDto<Collection<int, AdmissionSummaryDto>> */
    public function getDischargedAdmissions(string $beginDate, ?string $endDate = null, string|int|null $wardNumber = null): PortalResultDto
    {
        return $this->toListResult(
            (new AdmissionWardAPI)->getDischargeAdmissions($beginDate, $endDate, $wardNumber),
            fn (array $data) => AdmissionSummaryDto::fromPortal($data),
        );
    }

    protected function toResult(ResponseDto $response, callable $map): PortalResultDto
    {
        if (! $response->ok || ! $response->found) {
            return new PortalResultDto($response->ok, false, $response->message);
        }

        return new PortalResultDto(true, true, $response->message, $map($response->data));
    }

    /** List responses carry their records at the root of `data`. */
    protected function toListResult(ResponseDto $response, callable $map): PortalResultDto
    {
        return $this->toResult(
            $response,
            fn (array $data) => collect($data)->filter(fn ($row) => is_array($row))->map($map)->values(),
        );
    }
}
