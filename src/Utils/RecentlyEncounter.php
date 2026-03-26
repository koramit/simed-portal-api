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

        $latestAdmission = $ipdResponse->data['response']['total'] === 1
            ? AdmissionDto::fromDSL($ipdResponse->data['response']['entry'][0]['resource'])
            : null;

        $latestVisit = $opdResponse->data['response']['total'] > 1
            ? VisitDto::fromFHIRBundle($opdResponse->data)
            : null;

        return new RecentlyEncounterDto(
            admission: $latestAdmission,
            visit: $latestVisit,
        );
    }
}
