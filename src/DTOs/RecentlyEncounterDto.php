<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

readonly class RecentlyEncounterDto
{
    /**
     * `ok` is false when either Portal call failed, so a null `admission` / `visit`
     * means "none found" only when `ok` is true. The half that did succeed is still filled.
     */
    public function __construct(
        public ?AdmissionDto $admission,
        public ?VisitDto $visit,
        public bool $ok = true,
        public ?string $message = null,
    ) {}
}
