<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class AdmissionAPI
{
    use PortalCallable;

    public function getAdmissionWS(string|int $an, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, ['an' => $an]);
    }

    public function getPatientAdmissionsWS(string|int $hn, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'pt/adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, ['hn' => $hn]);
    }

    public function getPatientRecentlyAdmissionWS(string|int $hn, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'pt/adm/lst'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, ['hn' => $hn]);
    }

    public function getAdmissionEHIS(string|int $an, bool $raw = false, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'dsl/adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, [
            'an' => $an,
            'raw' => $raw,
        ]);
    }

    public function getPatientAdmissionsEHIS(string|int $hn, bool $raw = false, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'dsl/pt/adm'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, [
            'hn' => $hn,
            'raw' => $raw,
        ]);
    }

    public function getPatientRecentlyAdmissionEHIS(string|int $hn, bool $raw = false, bool $withSensitiveData = true): ResponseDto
    {
        $endpoint = 'dsl/pt/adm/lst'.($withSensitiveData ? '/sd' : '');

        return $this->makePost($endpoint, [
            'hn' => $hn,
            'raw' => $raw,
        ]);
    }

    public function getAdmissionDSL(string|int $an): ResponseDto
    {
        return (new EncounterAPI)->getEncounter(url: 'AN'.$an);
    }

    public function getPatientAdmissionsDSL(string|int $hn): ResponseDto
    {
        return (new EncounterAPI)->getEncounter(request: [
            'subject' => 'HN'.$hn,
            'class' => 'IMP',
            '_sort' => 'date',
            '_maxresults' => '1000',
        ]);
    }

    public function getPatientRecentlyAdmissionDSL(string|int $hn): ResponseDto
    {
        return (new EncounterAPI)->getEncounter(request: [
            'subject' => 'HN'.$hn,
            'class' => 'IMP',
            '_sort' => '-date',
            '_maxresults' => '1',
        ]);
    }
}
