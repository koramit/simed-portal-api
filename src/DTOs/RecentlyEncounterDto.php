<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

readonly class RecentlyEncounterDto
{
    public function __construct(
        public ?AdmissionDto $admission,
        public ?VisitDto $visit,
    ) {}
}
