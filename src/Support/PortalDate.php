<?php

namespace Koramit\SiMEDPortalAPI\Support;

use Illuminate\Support\Carbon;

/**
 * The one place Portal date strings are turned into Carbon instances.
 * Empty values (null, '', false) give null.
 */
final class PortalDate
{
    /** The timezone the Portal's offset-less strings are in (config simed-portal.timezone). */
    public static function timezone(): string
    {
        return (string) config('simed-portal.timezone') ?: 'Asia/Bangkok';
    }

    /**
     * A timestamp, as UTC. A string with an offset keeps its instant;
     * a string without one is read in the Portal timezone.
     */
    public static function utc(mixed $value): ?Carbon
    {
        return $value
            ? Carbon::parse((string) $value, self::timezone())->utc()
            : null;
    }

    /**
     * A calendar date sent as YYYY-MM-DD (dob, date of death): midnight in the host app's
     * timezone, not converted to UTC, so ->toDateString() is always the Portal's date.
     */
    public static function date(mixed $value): ?Carbon
    {
        return $value
            ? Carbon::parse((string) $value)
            : null;
    }
}
