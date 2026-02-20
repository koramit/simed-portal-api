<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class LabAPI
{
    use PortalCallable;

    public function getLabPendingReports(int|string $hn): array
    {
        return $this->makePost('lab-pending', ['hn' => (string) $hn]);
    }

    public function getLabRecentlyOrders(int|string $hn): array
    {
        return $this->makePost('lab-recently', ['hn' => (string) $hn]);
    }

    public function getLabFromRefNo(int|string $refNo): array
    {
        return $this->makePost('lab-from-ref-no', ['ref_no' => $refNo]);
    }

    public function getLabFromServiceId(int|string $hn, int|string|array $serviceIds, bool $recently = false, ?string $dateStart = null, ?string $dateEnd = null): array
    {
        return $this->makePost(
            'lab-from-service-id',
            [
                'hn' => (string) $hn,
                'service_ids' => is_array($serviceIds) ? $serviceIds : [$serviceIds],
                'latest' => $recently,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ]);
    }

    public function getLabFromItemCodeAllResults(string|int $hn, string $itemCode): array
    {
        return $this->makePost(
            'lab-from-item-all',
            [
                'hn' => (string) $hn,
                'item_code' => $itemCode,
            ]);
    }

    public function getLabFromItemCodes(string|int $hn, array|string $itemCodes, ?string $dateStart = null, ?string $dateEnd = null): array
    {
        return $this->makePost(
            'lab-from-item-code',
            [
                'hn' => (string) $hn,
                'item_codes' => is_array($itemCodes) ? $itemCodes : [$itemCodes],
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ]);
    }
}
