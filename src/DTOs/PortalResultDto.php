<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

/**
 * Typed result of a SiMEDPortalUtil call.
 *
 * - `ok: false`: the Portal or the upstream service failed; `message` says why, `data` is null.
 * - `ok: true, found: false`: the call worked but there is no such record; `data` is null.
 * - `ok: true, found: true`: `data` holds the DTO (or a Collection of DTOs for list calls).
 *
 * @template T
 */
readonly class PortalResultDto
{
    /** @param  T|null  $data */
    public function __construct(
        public bool $ok,
        public bool $found,
        public ?string $message = null,
        public mixed $data = null,
    ) {}
}
