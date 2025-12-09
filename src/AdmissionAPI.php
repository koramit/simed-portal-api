<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class AdmissionAPI
{
    use PortalCallable;

    public function getAdmission(string|int $an, bool $withSensitiveData = true): array
    {
        $endpoint = 'admission'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, ['an' => $an]);
    }

    public function getPatientAdmissions(string|int $an, bool $withSensitiveData = true): array
    {
        $endpoint = 'patient-admissions'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, ['an' => $an]);
    }

    public function getPatientRecentlyAdmission(string|int $hn, bool $withSensitiveData = true): array
    {
        $endpoint = 'patient-recently-admission'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, ['hn' => $hn]);
    }

    public function getAdmissionDSL(string|int $an, bool $raw = false, bool $withSensitiveData = true): array
    {
        $endpoint = 'dsl/admission'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, [
            'an' => $an,
            'raw' => $raw,
        ]);
    }

    public function getPatientAdmissionsDSL(string|int $hn, bool $raw = false, bool $withSensitiveData = true): array
    {
        $endpoint = 'dsl/patient-admissions'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, [
            'hn' => $hn,
            'raw' => $raw,
        ]);
    }

    public function getPatientRecentlyAdmissionDSL(string|int $hn, bool $raw = false, bool $withSensitiveData = true): array
    {
        $endpoint = 'dsl/patient-recently-admission'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, [
            'hn' => $hn,
            'raw' => $raw,
        ]);
    }
}
