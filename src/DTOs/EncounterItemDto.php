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

    public function isActive(): bool
    {
        return in_array($this->status, VisitDto::ACTIVE_STATUSES, true);
    }

    /** Null while the item is still active (its `end` is not a finish time yet). */
    public function getDurationInMinutes(): ?int
    {
        if ($this->isActive() || $this->end === null) {
            return null;
        }

        return (int) $this->start->diffInMinutes($this->end);
    }
}
