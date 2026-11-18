<?php

namespace Koramit\SiMEDPortalAPI\Utils;

use Illuminate\Support\Facades\Concurrency;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionDto;
use Koramit\SiMEDPortalAPI\DTOs\RecentlyEncounterDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitDto;
use Koramit\SiMEDPortalAPI\EncounterAPI;

class RecentlyEncounter
{
    public function __invoke(int $hn): RecentlyEncounterDto
    {
        $api = new EncounterAPI;

        [$ipdResponse, $opdResponse] = Concurrency::driver('process')->run([
            fn () => $api->getEncounter(request: [
                'subject' => 'HN'.$hn,
                'class' => 'IMP',
                '_maxresults' => 1,
                '_sort' => '-date',
            ]),
            fn () => $api->getEncounter(request: [
                'subject' => 'HN'.$hn,
                'class' => 'AMB',
                '_maxresults' => 1,
                '_sort' => '-date',
            ]),
        ]);

        // `total` counts entries in this response (an AMB visit comes with its child encounters),
        // so check the entries themselves.
        $ipdEntries = $ipdResponse->data['response']['entry'] ?? [];
        $opdEntries = $opdResponse->data['response']['entry'] ?? [];

        $latestAdmission = empty($ipdEntries)
            ? null
            : AdmissionDto::fromDSL($ipdEntries[0]);

        $latestVisit = empty($opdEntries)
            ? null
            : VisitDto::fromFHIRBundle($opdResponse->data);

        return new RecentlyEncounterDto(
            admission: $latestAdmission,
            visit: $latestVisit,
        );
    }
}
