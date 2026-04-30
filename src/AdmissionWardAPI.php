<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class AdmissionWardAPI
{
    use PortalCallable;

    public function getActiveAdmissions(?int $wardNumber = null): ResponseDto
    {
        return $this->makePost('w/adm', ['number' => $wardNumber]);
    }

    public function getDischargeAdmissions(string $beginDate, ?string $endDate = null, ?int $wardNumber = null): ResponseDto
    {
        return $this->makePost('adm/dc', [
            'begin_date' => $beginDate,
            'end_date' => $endDate,
            'number' => $wardNumber,
        ]);
    }

    public function getWardTransferWS(int $an): ResponseDto
    {
        return $this->makePost('adm/mv', ['an' => $an]);
    }
}
