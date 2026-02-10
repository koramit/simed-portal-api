<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class LabAPI
{
    use PortalCallable;

    public function getLabFromItemCodeAllResults(string|int $hn, string $itemCode): array
    {
        return $this->makePost(
            'lab-from-item-all',
            [
                'hn' => (string) $hn,
                'item_code' => $itemCode,
            ]);
    }
}
