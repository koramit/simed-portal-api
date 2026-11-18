<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

readonly class AdmissionWardSummaryDto
{
    public function __construct(
        public int $portal_id,
        /** The ward's eHIS number (Portal `wards.number`), kept as stored; null when unknown. */
        public ?string $ws_id,
        public string $name,
        public string $name_short,
        public int $admissions_count,
    ) {}

    public static function fromPortal(array $data): static
    {
        return new static(
            $data['ward_number'],
            isset($data['ward_ref_id']) ? (string) $data['ward_ref_id'] : null,
            $data['ward_name'],
            $data['ward_name_short'],
            $data['admissions_count'],
        );
    }
}
