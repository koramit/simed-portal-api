<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class AdmissionWardAPI
{
    use PortalCallable;

    public function getActiveAdmissions(?int $wardNumber = null): array
    {
        return $this->makePost('w/adm', ['number' => $wardNumber]);
    }

    public function getDischargeAdmissions(string $beginDate, ?string $endDate = null, ?int $wardNumber = null): array
    {
        return $this->makePost('adm/dc', [
            'begin_date' => $beginDate,
            'end_date' => $endDate,
            'number' => $wardNumber,
        ]);
    }
}
