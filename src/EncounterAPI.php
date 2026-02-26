<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\Traits\PortalCallable;

class EncounterAPI
{
    use PortalCallable;

    public function getEncounter(
        string|int|null $hn = null,
        ?string $dateStart = null,
        ?string $dateEnd = null,
        ?string $status = null,
        ?string $id = null,
        ?string $partOf = null,
        ?array $request = null
    ): array {
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
        if ($partOf) {
            $payload['id'] = $partOf;
        }
        if (! empty($request)) {
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
