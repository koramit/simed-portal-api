<?php

namespace Koramit\SiMEDPortalAPI\Utils;

use Koramit\SiMEDPortalAPI\DTOs\VisitBundleDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitDto;
use Koramit\SiMEDPortalAPI\EncounterAPI;

class VisitBundle
{
    public function __invoke(string|int $vn): VisitBundleDto
    {
        $dto = (new EncounterAPI)->getEncounter(request: [
            'identifier' => "http://si.mahidol.ac.th/mrs/out_patient_visit/vn|$vn",
            'class' => 'AMB', // We have to provide a class to get a visit type
        ]);

        // A found bundle without a parent visit entry counts as not found.
        $visit = $dto->found ? VisitDto::fromFHIRBundle($dto->data) : null;

        return $visit
            ? new VisitBundleDto($dto->ok, true, null, $visit)
            : new VisitBundleDto($dto->ok, false, $dto->message);
    }
}
