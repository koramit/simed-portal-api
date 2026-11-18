<?php

use Koramit\SiMEDPortalAPI\DTOs\VisitDto;

it('parses the parent visit and its items in start order', function () {
    $visit = VisitDto::fromFHIRBundle(portalFixture('visit-bundle'));

    expect($visit)
        ->vn->toBe('VN0000001')
        ->hn->toBe('00000099')
        ->type->toBe('ผู้ป่วยนอก')
        ->active->toBeFalse()
        ->and($visit->start->toIso8601String())->toBe('2026-11-17T02:58:17+00:00')
        ->and($visit->items->pluck('id')->all())->toBe(['EN0000002', 'EN0000001', 'EN0000003']);
});

it('measures duration as the union of item intervals, not the parent end', function () {
    // Children overlap (09:58:17–13:28:09 and 10:24:17–13:39:58), a third sits inside;
    // the parent was auto-closed after midnight.
    expect(VisitDto::fromFHIRBundle(portalFixture('visit-bundle'))->getDurationInMinutes())->toBe(221);
});

it('gives null for missing item details and does not count them as a clinic', function () {
    $visit = VisitDto::fromFHIRBundle(portalFixture('visit-bundle'));
    $bare = $visit->items->firstWhere('id', 'EN0000003');

    expect($bare)
        ->clinic_code->toBeNull()
        ->clinic_name->toBeNull()
        ->location->toBeNull()
        ->department->toBeNull()
        ->doctor->toBeNull()
        ->and($visit->countDistinctClinics())->toBe(2)
        ->and($visit->items->firstWhere('id', 'EN0000001'))
        ->clinic_code->toBe('C01')
        ->doctor->toBe('นพ.ตรวจ ทดสอบ');
});

it('has no duration while the visit is in progress', function () {
    $visit = VisitDto::fromFHIRBundle(portalFixture('visit-bundle-active'));

    expect($visit)
        ->active->toBeTrue()
        ->and($visit->getDurationInMinutes())->toBeNull()
        ->and($visit->items->sole()->isActive())->toBeTrue()
        ->and($visit->items->sole()->getDurationInMinutes())->toBeNull();
});

it('returns null without a parent entry', function (array $bundle) {
    expect(VisitDto::fromFHIRBundle($bundle))->toBeNull();
})->with([
    'empty bundle' => [['response' => ['entry' => []]]],
    'children only' => fn () => ['response' => ['entry' => array_slice(portalFixture('visit-bundle')['response']['entry'], 1)]],
]);
