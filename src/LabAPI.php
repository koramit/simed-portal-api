<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class LabAPI
{
    use PortalCallable;

    public function getLabPendingReports(string|int $hn): ResponseDto
    {
        return $this->makePost('lab-pending', ['hn' => $hn]);
    }

    public function getLabRecentOrders(string|int $hn): ResponseDto
    {
        return $this->makePost('lab-recently', ['hn' => $hn]);
    }

    public function getLabFromRefNo(int|string $refNo): ResponseDto
    {
        return $this->makePost('lab-from-ref-no', ['ref_no' => $refNo]);
    }

    public function getLabFromServiceId(string|int $hn, int|string|array $serviceIds, bool $latest = false, ?string $dateStart = null, ?string $dateEnd = null): ResponseDto
    {
        return $this->makePost(
            'lab-from-service-id',
            [
                'hn' => $hn,
                'service_ids' => is_array($serviceIds) ? $serviceIds : [$serviceIds],
                'latest' => $latest,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ]);
    }

    public function getLabFromItemCodeAllResults(string|int $hn, string $itemCode): ResponseDto
    {
        return $this->makePost(
            'lab-from-item-all',
            [
                'hn' => $hn,
                'item_code' => $itemCode,
            ]);
    }

    public function getLabFromItemCodes(string|int $hn, array|string $itemCodes, ?string $dateStart = null, ?string $dateEnd = null): ResponseDto
    {
        return $this->makePost(
            'lab-from-item-code',
            [
                'hn' => $hn,
                'item_codes' => is_array($itemCodes) ? $itemCodes : [$itemCodes],
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ]);
    }
}
