<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class AdmissionAPI
{
    use PortalCallable;

    public function getAdmissionWS(string|int $an, bool $withSensitiveData = true): array
    {
        $endpoint = 'adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, ['an' => $an]);
    }

    public function getPatientAdmissionsWS(string|int $hn, bool $withSensitiveData = true): array
    {
        $endpoint = 'pt/adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, ['hn' => $hn]);
    }

    public function getPatientRecentlyAdmissionWS(string|int $hn, bool $withSensitiveData = true): array
    {
        $endpoint = 'pt/adm/lst'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, ['hn' => $hn]);
    }

    public function getAdmissionEHIS(string|int $an, bool $raw = false, bool $withSensitiveData = true): array
    {
        $endpoint = 'dsl/adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, [
            'an' => $an,
            'raw' => $raw,
        ]);
    }

    public function getPatientAdmissionsEHIS(string|int $hn, bool $raw = false, bool $withSensitiveData = true): array
    {
        $endpoint = 'dsl/pt/adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, [
            'hn' => $hn,
            'raw' => $raw,
        ]);
    }

    public function getPatientRecentlyAdmissionEHIS(string|int $hn, bool $raw = false, bool $withSensitiveData = true): array
    {
        $endpoint = 'dsl/pt/adm/lst'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, [
            'hn' => $hn,
            'raw' => $raw,
        ]);
    }
}
