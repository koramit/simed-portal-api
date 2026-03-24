<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class PatientAPI
{
    use PortalCallable;

    public function getPatientWS(string|int $hn, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'pt'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, ['hn' => $hn]);
    }

    public function getPatientDSL(string $keyValue, string $keyName = 'hn', bool $raw = false, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'dsl/pt'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, [
            'key_name' => $keyName,
            'key_value' => $keyValue,
            'raw' => $raw,
        ]);
    }
}
