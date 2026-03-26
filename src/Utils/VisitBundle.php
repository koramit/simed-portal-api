<?php

namespace Koramit\SiMEDPortalAPI\Utils;

use Koramit\SiMEDPortalAPI\DTOs\VisitBundleDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitDto;
use Koramit\SiMEDPortalAPI\EncounterAPI;

class VisitBundle
{
    public function __invoke(int $vn): VisitBundleDto
    {
        $dto = (new EncounterAPI)->getEncounter(request: [
            'identifier' => "http://si.mahidol.ac.th/mrs/out_patient_visit/vn|$vn",
            'class' => 'AMB', // We have to provide a class to get a visit type
        ]);

        return $dto->found
            ? new VisitBundleDto(
                $dto->ok,
                $dto->found,
                null,
                VisitDto::fromFHIRBundle($dto->data),
            )
            : new VisitBundleDto(
                $dto->ok,
                $dto->found,
                $dto->message,
            );
    }
}
