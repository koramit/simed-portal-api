<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class EncounterAPI
{
    use PortalCallable;

    public function getEncounter(string|int|null $hn, ?string $dateStart, ?string $dateEnd, ?string $status, ?string $id, ?string $partOf, ?string $request): array
    {
        $payload = [];
        if ($hn) {
            $payload['hn'] = (string) $hn;
        }
        if ($dateStart) {
            $payload['date_start'] = $dateStart;
        }
        if ($dateEnd) {
            $payload['date_end'] = $dateEnd;
        }
        if ($status) {
            $payload['status'] = $status;
        }
        if ($id) {
            $payload['id'] = $id;
        }
        if ($request) {
            $payload['request'] = $request;
        }

        return empty($payload)
            ? [
                'ok' => false,
                'found' => false,
                'message' => 'No parameter provided.',
            ]
            : $this->makePost('encounter', $payload);
    }
}
