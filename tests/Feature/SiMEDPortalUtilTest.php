<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionDto;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionSummaryDto;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionWardSummaryDto;
use Koramit\SiMEDPortalAPI\DTOs\ADUserDto;
use Koramit\SiMEDPortalAPI\DTOs\PatientDto;
use Koramit\SiMEDPortalAPI\DTOs\VisitDto;
use Koramit\SiMEDPortalAPI\Facades\SiMEDPortalUtil;

function ipdSearch(): array
{
    return ['ok' => true, 'found' => true, 'response' => ['entry' => [['resource' => portalFixture('dsl-admission')['response']]]]];
}

it('returns typed single results', function (string $method, array $args, string $route, string $fixture, string $dto) {
    Http::fake(["*/$route" => Http::response(portalFixture($fixture))]);

    $result = SiMEDPortalUtil::$method(...$args);

    expect($result)->ok->toBeTrue()->found->toBeTrue()->data->toBeInstanceOf($dto);
})->with([
    'patient WS' => ['getPatientWS', [99], 'pt/sd', 'patient-ws', PatientDto::class],
    'patient DSL' => ['getPatientDSL', ['99'], 'dsl/pt/sd', 'patient-dsl', PatientDto::class],
    'user' => ['getUser', ['tester.sys'], 'user', 'user-lookup', ADUserDto::class],
    'authenticate' => ['authenticate', ['tester.sys', 'secret'], 'authenticate', 'user-authenticated', ADUserDto::class],
    'admission WS' => ['getAdmissionWS', [2], 'adm/sd', 'ws-admission', AdmissionDto::class],
    'admission DSL' => ['getAdmissionDSL', [1], 'en', 'dsl-admission', AdmissionDto::class],
]);

it('returns ward lists from the `admissions` key', function (string $method, array $args, string $fixture, string $dto, int $count, array $payload) {
    Http::fake(['*' => Http::response(portalFixture($fixture))]);

    $result = SiMEDPortalUtil::$method(...$args);

    expect($result)->ok->toBeTrue()->found->toBeTrue()
        ->and($result->data)->toHaveCount($count)
        ->each->toBeInstanceOf($dto);
    Http::assertSent(fn (Request $r) => $r->data() === $payload);
})->with([
    'active admissions of a ward' => ['getWardActiveAdmissions', [12], 'ward-admissions', AdmissionSummaryDto::class, 2, ['number' => '12']],
    'wards with active admissions' => ['getActiveAdmissionWards', [], 'wards', AdmissionWardSummaryDto::class, 2, ['number' => null]],
    'discharges of a ward' => ['getDischargedAdmissions', [12, '2026-11-17'], 'ward-discharges', AdmissionSummaryDto::class, 1,
        ['begin_date' => '2026-11-17', 'end_date' => null, 'number' => '12']],
    'wards with discharges' => ['getDischargedAdmissionWards', ['2026-11-17', '2026-11-18'], 'wards', AdmissionWardSummaryDto::class, 2,
        ['begin_date' => '2026-11-17', 'end_date' => '2026-11-18', 'number' => null]],
]);

it('reports not found and failures without data', function (array $body, int $status, bool $ok) {
    Http::fake(['*' => Http::response($body, $status)]);

    expect(SiMEDPortalUtil::getPatientWS(99))->ok->toBe($ok)->found->toBeFalse()->data->toBeNull()
        ->and(SiMEDPortalUtil::getWardActiveAdmissions(12))->ok->toBe($ok)->found->toBeFalse()->data->toBeNull();
})->with([
    'not found' => [['ok' => true, 'found' => false], 200, true],
    'Portal error' => [['ok' => false, 'found' => false], 500, false],
]);

it('gets a visit bundle by VN', function () {
    Http::fake(['*/en' => Http::response(portalFixture('visit-bundle'))]);

    $bundle = SiMEDPortalUtil::getVisitBundle('VN0000001');

    expect($bundle)->ok->toBeTrue()->found->toBeTrue()->visit->toBeInstanceOf(VisitDto::class)
        ->and($bundle->visit->getDurationInMinutes())->toBe(221);
    Http::assertSent(fn (Request $r) => $r['request']['identifier'] === 'http://si.mahidol.ac.th/mrs/out_patient_visit/vn|VN0000001');
});

it('gets the latest admission and visit together', function () {
    Http::fake(['*/en' => fn (Request $r) => Http::response(
        $r['request']['class'] === 'IMP' ? ipdSearch() : portalFixture('visit-bundle')
    )]);

    $latest = SiMEDPortalUtil::getPatientLatestEncounters(99);

    expect($latest)->ok->toBeTrue()
        ->and($latest->admission->an)->toBe(1)
        ->and($latest->visit->vn)->toBe('VN0000001');
    Http::assertSent(fn (Request $r) => $r['request']['subject'] === 'HN99' && $r['request']['_maxresults'] === 1);
});

it('keeps the half that succeeded when one latest-encounter search fails', function () {
    Http::fake(['*/en' => fn (Request $r) => $r['request']['class'] === 'IMP'
        ? Http::response(ipdSearch())
        : Http::response(['ok' => false, 'found' => false], 500)]);

    $latest = SiMEDPortalUtil::getPatientLatestEncounters(99);

    expect($latest)->ok->toBeFalse()->visit->toBeNull()
        ->message->toBe(config('simed-portal.service_failed_message'))
        ->and($latest->admission->an)->toBe(1);
});
