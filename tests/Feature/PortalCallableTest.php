<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Koramit\SiMEDPortalAPI\AdmissionWardAPI;
use Koramit\SiMEDPortalAPI\ADUserAPI;
use Koramit\SiMEDPortalAPI\EncounterAPI;
use Koramit\SiMEDPortalAPI\Exceptions\PortalNotConfiguredException;
use Koramit\SiMEDPortalAPI\LabAPI;
use Koramit\SiMEDPortalAPI\PatientAPI;
use Koramit\SiMEDPortalAPI\Tests\TestCase;

it('posts JSON with the bearer token to endpoint/route', function () {
    config(['simed-portal.endpoint' => TestCase::ENDPOINT.'/']);
    Http::fake(['*' => Http::response(portalFixture('patient-ws'))]);

    (new PatientAPI)->getPatientWS(99);

    Http::assertSent(fn (Request $request) => $request->url() === TestCase::ENDPOINT.'/pt/sd'
        && $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request->hasHeader('Accept', 'application/json'));
});

it('sends hn, an and ward number as strings', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'found' => false])]);

    (new PatientAPI)->getPatientWS(12345678, withSensitiveData: false);
    (new AdmissionWardAPI)->getWardTransferWS(87654321);
    (new AdmissionWardAPI)->getActiveAdmissions(12);

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/pt') && $r['hn'] === '12345678');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/adm/mv') && $r['an'] === '87654321');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/w/adm') && $r['number'] === '12');
});

it('returns the body without the envelope keys when found', function () {
    Http::fake(['*' => Http::response(portalFixture('patient-ws'))]);

    $response = (new PatientAPI)->getPatientWS(99);

    expect($response)
        ->ok->toBeTrue()
        ->found->toBeTrue()
        ->status->toBe(200)
        ->and($response->data)->not->toHaveKeys(['ok', 'found'])
        ->and($response->data['hn'])->toBe('00000099');
});

it('reports not found with ok: true', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'found' => false, 'message' => 'not found'])]);

    expect((new PatientAPI)->getPatientWS(99))->ok->toBeTrue()->found->toBeFalse()->message->toBe('not found');
});

it('reports an upstream failure the Portal passed through as ok: false', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'found' => false, 'status' => 500, 'body' => 'eHIS down'])]);

    expect((new PatientAPI)->getPatientDSL('99'))
        ->ok->toBeFalse()
        ->status->toBe(500)
        ->message->toBe(config('simed-portal.service_failed_message'));
});

it('reports a Portal error status as ok: false', function () {
    Http::fake(['*' => Http::response(['ok' => false, 'found' => false, 'message' => 'server'], 500)]);

    expect((new PatientAPI)->getPatientWS(99))->ok->toBeFalse()->found->toBeFalse()->status->toBe(500);
});

it('retries connection errors, then reports 503', function () {
    Http::fake(['*' => Http::failedConnection()]);

    expect((new PatientAPI)->getPatientWS(99))->ok->toBeFalse()->status->toBe(503);
    Http::assertSentCount(3);
});

it('does not retry a received response', function () {
    Http::fake(['*' => Http::response('', 502)]);

    (new PatientAPI)->getPatientWS(99);

    Http::assertSentCount(1);
});

it('throws a Portal 422 as a ValidationException keyed by field', function () {
    Http::fake(['*' => Http::response(['message' => 'invalid', 'errors' => ['hn' => ['The hn field must be 8 digits.']]], 422)]);

    expect(fn () => (new PatientAPI)->getPatientWS('abc'))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toBe(['hn' => ['The hn field must be 8 digits.']]));
});

it('fails loudly when the endpoint or token is missing', function (string $key) {
    config(["simed-portal.$key" => null]);
    Http::fake();

    expect(fn () => (new PatientAPI)->getPatientWS(99))->toThrow(PortalNotConfiguredException::class);
    Http::assertNothingSent();
})->with(['endpoint', 'token']);

it('looks up a user by org_id only for an all-digit id', function (string|int $id, string $key) {
    Http::fake(['*' => Http::response(['ok' => true, 'found' => false])]);

    (new ADUserAPI)->getUser($id);

    Http::assertSent(fn (Request $r) => $r->data() === [$key => $id]);
})->with([
    [10012345, 'org_id'],
    ['10012345', 'org_id'],
    ['tester.sys', 'login'],
    ['1e5', 'login'],
]);

it('sends the lab and encounter payloads the Portal expects', function () {
    Http::fake(['*' => Http::response(['ok' => true, 'found' => false])]);

    (new LabAPI)->getLabFromServiceId(99, 'S1', latest: true);
    (new EncounterAPI)->getEncounter(partOf: 'VN0000001');

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/lab-from-service-id')
        && $r['service_ids'] === ['S1'] && $r['latest'] === true && $r['hn'] === '99');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/en') && $r->data() === ['part_of' => 'VN0000001']);
});

it('runs pooled encounter searches and keeps their keys', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['ok' => true, 'found' => true, 'response' => ['entry' => []]])
        ->push(['ok' => true, 'found' => false])]);

    $responses = (new EncounterAPI)->getEncounters(['ipd' => ['class' => 'IMP'], 'opd' => ['class' => 'AMB']]);

    expect($responses)->toHaveKeys(['ipd', 'opd'])
        ->and($responses['ipd']->ok)->toBeTrue();
    Http::assertSentCount(2);
});
