<?php

namespace Koramit\SiMEDPortalAPI\Utils;

use Koramit\SiMEDPortalAPI\DTOs\AdmissionDto;
use Koramit\SiMEDPortalAPI\DTOs\LatestEncountersDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitDto;
use Koramit\SiMEDPortalAPI\EncounterAPI;

class LatestEncounters
{
    public function __invoke(string|int $hn): LatestEncountersDto
    {
        // Both searches run concurrently in-process (Http::pool), so Http::fake() works in host-app tests.
        ['ipd' => $ipdResponse, 'opd' => $opdResponse] = (new EncounterAPI)->getEncounters([
            'ipd' => [
                'subject' => 'HN'.$hn,
                'class' => 'IMP',
                '_maxresults' => 1,
                '_sort' => '-date',
            ],
            'opd' => [
                'subject' => 'HN'.$hn,
                'class' => 'AMB',
                '_maxresults' => 1,
                '_sort' => '-date',
            ],
        ]);

        // `total` counts entries in this response (an AMB visit comes with its child encounters),
        // so check the entries themselves. A failed call has `data: []`, so it yields no entries.
        $ipdEntries = $ipdResponse->ok ? ($ipdResponse->data['response']['entry'] ?? []) : [];
        $opdEntries = $opdResponse->ok ? ($opdResponse->data['response']['entry'] ?? []) : [];

        $latestAdmission = empty($ipdEntries)
            ? null
            : AdmissionDto::fromDSL($ipdEntries[0]);

        // Null when the bundle holds no parent visit entry.
        $latestVisit = empty($opdEntries)
            ? null
            : VisitDto::fromFHIRBundle($opdResponse->data);

        $failed = collect([$ipdResponse, $opdResponse])->first(fn ($response) => ! $response->ok);

        return new LatestEncountersDto(
            admission: $latestAdmission,
            visit: $latestVisit,
            ok: $failed === null,
            message: $failed?->message,
        );
    }
}
