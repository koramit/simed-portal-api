<?php

use Illuminate\Support\Facades\Http;
use Koramit\SiMEDPortalAPI\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');

beforeEach(fn () => Http::preventStrayRequests());

/** Decoded JSON fixture from tests/Fixtures (synthetic, anonymised data). */
function portalFixture(string $name): array
{
    return json_decode(file_get_contents(__DIR__.'/Fixtures/'.$name.'.json'), true, flags: JSON_THROW_ON_ERROR);
}
