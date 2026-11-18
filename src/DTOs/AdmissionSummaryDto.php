<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;
use Koramit\SiMEDPortalAPI\Support\PortalDate;

readonly class AdmissionSummaryDto
{
    public function __construct(
        public int $an,
        public int $hn,
        public string $name,
        public string $gender,
        /** Null when the Portal has no date of birth for the patient. */
        public ?int $age,
        public ?string $age_unit,
        public Carbon $admitted_at,
        public ?string $discharge_status = null,
        public ?string $discharge_type = null,
        public ?Carbon $discharged_at = null,
    ) {}

    public static function fromPortal(array $data): static
    {
        return new static(
            $data['an'],
            $data['hn'],
            $data['name'],
            $data['gender'],
            $data['age'] ?? null,
            $data['age_unit'] ?? null,
            PortalDate::wallClockUtc($data['admitted_at']),
            $data['discharge_status'] ?? null,
            $data['discharge_type'] ?? null,
            PortalDate::wallClockUtc($data['discharged_at'] ?? null),
        );
    }
}
