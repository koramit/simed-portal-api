<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

readonly class VisitBundleDto
{
    public function __construct(
        public bool $ok,
        public bool $found,
        public ?string $message,
        public ?VisitDto $visit = null,
    ) {}
}
