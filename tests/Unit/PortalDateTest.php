<?php

use Koramit\SiMEDPortalAPI\Support\PortalDate;

it('keeps the instant of a timestamp that carries an offset', function (string $value) {
    expect(PortalDate::utc($value)->toIso8601String())->toBe('2026-11-17T01:21:00+00:00');
})->with([
    '+07:00' => '2026-11-17T08:21:00+07:00',
    'Z' => '2026-11-17T01:21:00Z',
]);

it('reads an offset-less timestamp in the Portal timezone, whatever the app timezone', function (string $appTimezone) {
    date_default_timezone_set($appTimezone);

    expect(PortalDate::utc('2026-11-17 08:21:00')->toIso8601String())->toBe('2026-11-17T01:21:00+00:00');
})->with(['UTC', 'Asia/Bangkok', 'America/New_York']);

it('uses the configured Portal timezone', function () {
    config(['simed-portal.timezone' => 'Asia/Tokyo']);

    expect(PortalDate::utc('2026-11-17 09:00:00')->toIso8601String())->toBe('2026-11-17T00:00:00+00:00');
});

it('reads a fake-Z timestamp as Portal wall-clock time', function () {
    expect(PortalDate::wallClockUtc('2026-11-17T08:21:00.000000Z')->toIso8601String())
        ->toBe('2026-11-17T01:21:00+00:00');
});

it('keeps a calendar date on the same day', function (string $appTimezone) {
    date_default_timezone_set($appTimezone);

    expect(PortalDate::date('1980-05-02')->toDateString())->toBe('1980-05-02');
})->with(['UTC', 'Asia/Bangkok']);

it('returns null for empty values', function (mixed $value) {
    expect(PortalDate::utc($value))->toBeNull()
        ->and(PortalDate::wallClockUtc($value))->toBeNull()
        ->and(PortalDate::date($value))->toBeNull();
})->with([null, '', false]);
