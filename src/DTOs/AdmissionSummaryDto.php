<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;

readonly class AdmissionSummaryDto
{
    public function __construct(
        public int $an,
        public int $hn,
        public string $name,
        public string $gender,
        public int $age,
        public string $age_unit,
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
            $data['age'],
            $data['age_unit'],
            Carbon::parse($data['admitted_at'], 'Asia/Bangkok')
                ->shiftTimezone('Asia/Bangkok')
                ->setTimezone('UTC'),
            $data['discharge_status'] ?? null,
            $data['discharge_type'] ?? null,
            ($data['discharged_at'] ?? null)
                ? Carbon::parse($data['discharged_at'])->shiftTimezone('Asia/Bangkok')->setTimezone('UTC')
                : null,
        );
    }
}
