<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class Patient
{
    use PortalCallable;

    public function getPatient(string|int $hn, bool $withSensitiveData = true): array
    {
        $endpoint = 'patient'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, ['hn' => $hn]);
    }

    public function getPatientDSL(string $keyValue, string $keyName = 'hn', bool $raw = false, bool $withSensitiveData = true): array
    {
        $endpoint = 'dsl/patient'.($withSensitiveData ? '-with-sensitive-data' : '');

        return $this->makePost($endpoint, [
            'key_name' => $keyName,
            'key_value' => $keyValue,
            'raw' => $raw,
        ]);
    }
}
