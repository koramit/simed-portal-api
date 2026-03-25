<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;

readonly class EncounterItemDto
{
    public function __construct(
        public string $id,
        public string $clinic_code,
        public string $clinic_name,
        public string $location,
        public string $department,
        public string $doctor,
        public Carbon $start,
        public ?Carbon $end,
        public string $status,
    ) {}

    public function getDurationInMinutes(): int
    {
        return $this->end ? $this->start->diff($this->end)->i : 0;
    }
}
