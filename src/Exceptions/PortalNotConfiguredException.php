<?php

namespace Koramit\SiMEDPortalAPI\Exceptions;

use RuntimeException;

/**
 * Thrown when `simed-portal.endpoint` or `simed-portal.token` is empty: a deployment
 * mistake in the host app, not a Portal failure, so it is not turned into a ResponseDto.
 */
class PortalNotConfiguredException extends RuntimeException
{
    public static function missing(string $key, string $env): static
    {
        return new static("SiMED Portal is not configured: set {$env} (config simed-portal.{$key}).");
    }
}
