<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

readonly class AdmissionWardSummaryDto
{
    public function __construct(
        public int $portal_id,
        public int $ws_id,
        public string $name,
        public string $name_short,
        public int $admissions_count,
    ) {}

    public static function fromPortal(array $data): static
    {
        return new static(
            $data['ward_number'],
            $data['ward_ref_id'],
            $data['ward_name'],
            $data['ward_name_short'],
            $data['admissions_count'],
        );
    }
}
