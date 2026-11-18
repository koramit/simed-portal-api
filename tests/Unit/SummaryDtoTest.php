<?php

use Koramit\SiMEDPortalAPI\DTOs\AdmissionSummaryDto;
use Koramit\SiMEDPortalAPI\DTOs\AdmissionWardSummaryDto;
use Koramit\SiMEDPortalAPI\DTOs\ADUserDto;
use Koramit\SiMEDPortalAPI\DTOs\PatientDto;

it('reads the ward routes\' fake-Z times as Bangkok wall-clock time', function () {
    $summary = AdmissionSummaryDto::fromPortal(portalFixture('ward-discharges')['admissions'][0]);

    expect($summary)
        ->an->toBe(4)
        ->discharge_status->toBe('Improved')
        ->and($summary->admitted_at->toIso8601String())->toBe('2026-11-10T01:30:00+00:00')
        ->and($summary->discharged_at->toIso8601String())->toBe('2026-11-17T03:15:00+00:00');
});

it('allows a patient without age (no date of birth on the Portal)', function () {
    $summary = AdmissionSummaryDto::fromPortal(portalFixture('ward-admissions')['admissions'][1]);

    expect($summary)->age->toBeNull()->age_unit->toBeNull()->discharged_at->toBeNull();
});

it('keeps the ward eHIS number as stored', function () {
    [$withNumber, $withoutNumber] = array_map(
        fn (array $row) => AdmissionWardSummaryDto::fromPortal($row),
        portalFixture('wards')['admissions'],
    );

    expect($withNumber)->portal_id->toBe(1)->ws_id->toBe('01216')->admissions_count->toBe(28)
        ->and($withoutNumber->ws_id)->toBeNull();
});

it('parses WS and DSL patients the same way', function (string $fixture) {
    $patient = PatientDto::fromWS(portalFixture($fixture));

    expect($patient)
        ->hn->toBe(99)
        ->date_death->toBeNull()
        ->photo->toBe([])
        ->and($patient->dob->toDateString())->toBe('1980-05-02')
        ->and(PatientDto::fromDSL(portalFixture($fixture)))->toEqual($patient);
})->with(['patient-ws', 'patient-dsl']);

it('builds a user from any Portal user path', function (string $fixture, ?string $email) {
    $user = ADUserDto::fromPortal(portalFixture($fixture));

    expect($user)->login->toBe('tester.sys')->org_id->toBe('10012345')->email->toBe($email);
})->with([
    'lookup (no email / names in English)' => ['user-lookup', null],
    'authenticate' => ['user-authenticated', 'tester@example.test'],
]);

it('builds a user from the ALT authentication fallback', function () {
    $user = ADUserDto::fromPortal([
        'ok' => true, 'found' => true, 'alt' => true, 'login' => 'tester.sys', 'org_id' => '10012345',
        'full_name' => 'Tester System', 'email' => 'tester@example.test', 'password_expires_in_days' => 30,
    ]);

    expect($user)->full_name->toBe('Tester System')->position_id->toBeNull()->password_expires_in_days->toBe(30);
});
