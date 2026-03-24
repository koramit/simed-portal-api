<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

readonly class ResponseDto
{
    public function __construct(
        public bool $ok,
        public bool $found,
        public int $status,
        public ?string $message,
        public array $data,
    ) {}
}
