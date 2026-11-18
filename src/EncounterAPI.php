<?php

namespace Koramit\SiMEDPortalAPI;

use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
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
        ?string $url = null,
        ?array $request = null
    ): ResponseDto {
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
            $payload['part_of'] = $partOf;
        }
        if ($url) {
            $payload['url'] = $url;
        }
        if (! empty($request)) {
            $payload['request'] = $request;
        }

        return empty($payload)
            ? new ResponseDto(
                false,
                false,
                422,
                'No parameter provided.',
                []
            )
            : $this->makePost('en', $payload);
    }

    /**
     * Run several `request:` searches concurrently (one round trip in wall time).
     *
     * @param  array<string, array>  $requests  key => the `request` array getEncounter() would take
     * @return array<string, ResponseDto> same keys
     */
    public function getEncounters(array $requests): array
    {
        return $this->makePostPool(
            array_map(fn (array $request) => ['en', ['request' => $request]], $requests)
        );
    }
}
