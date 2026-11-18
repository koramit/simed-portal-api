<?php

use Koramit\SiMEDPortalAPI\DTOs\AdmissionDto;
use Koramit\SiMEDPortalAPI\DTOs\WardLocationDto;

describe('fromDSL', function () {
    it('reads identifiers, times and discharge details', function () {
        $admission = AdmissionDto::fromDSL(portalFixture('dsl-admission'));

        expect($admission)
            ->hn->toBe(99)
            ->an->toBe(1)
            ->patient_name->toBe('นายทดสอบ ระบบ')
            ->status->toBe('finished')
            ->active->toBeFalse()
            ->discharge_status->toBe('Improved')
            ->discharge_type->toBe('With approval')
            ->and($admission->admitted_at->toIso8601String())->toBe('2026-11-10T01:30:00+00:00')
            ->and($admission->discharged_at->toIso8601String())->toBe('2026-11-13T07:00:00+00:00');
    });

    it('matches practitioners by role, not position', function () {
        $data = portalFixture('dsl-admission');
        $data['response']['participant'] = array_reverse($data['response']['participant']);

        expect(AdmissionDto::fromDSL($data))
            ->admit_practitioner->toBe('พญ.รับไว้ ทดสอบ')
            ->attend_practitioner->toBe('นพ.เจ้าของไข้ ทดสอบ')
            ->discharge_practitioner->toBe('นพ.จำหน่าย ทดสอบ');
    });

    it('falls back to the admitting doctor until an attending doctor is recorded', function () {
        $data = portalFixture('dsl-admission');
        $data['response']['participant'] = [$data['response']['participant'][0]];

        expect(AdmissionDto::fromDSL($data))
            ->attend_practitioner->toBe('พญ.รับไว้ ทดสอบ')
            ->discharge_practitioner->toBeNull();
    });

    it('tolerates an active admission without participants, extensions or locations', function () {
        $data = portalFixture('dsl-admission');
        $data['response']['status'] = 'in-progress';
        unset(
            $data['response']['period']['end'],
            $data['response']['participant'],
            $data['response']['hospitalization'],
            $data['response']['location'],
        );

        expect(AdmissionDto::fromDSL($data))
            ->active->toBeTrue()
            ->discharged_at->toBeNull()
            ->admit_practitioner->toBeNull()
            ->attend_practitioner->toBeNull()
            ->discharge_status->toBeNull()
            ->locations->toBeEmpty();
    });

    it('accepts a search-bundle entry (`resource`) as well as a read (`response`)', function () {
        $entry = ['resource' => portalFixture('dsl-admission')['response']];

        expect(AdmissionDto::fromDSL($entry)->an)->toBe(1);
    });

    it('merges consecutive stays per ward, drops zero-length entries, newest first', function () {
        $locations = AdmissionDto::fromDSL(portalFixture('dsl-admission'))->locations;

        expect($locations)->toHaveCount(2)
            ->and($locations->map(fn (WardLocationDto $l) => [
                $l->ward_code, $l->ward_name, $l->bed, $l->room, $l->start->toIso8601String(), $l->end->toIso8601String(),
            ])->all())->toBe([
                ['1218', 'อายุรศาสตร์หญิง 2', 'B05', 'R2', '2026-11-11T02:00:00+00:00', '2026-11-13T07:00:00+00:00'],
                // two beds in ward 1216 merged: earliest start, latest end, latest bed
                ['1216', 'อายุรศาสตร์ชาย 1', 'B14', 'R3', '2026-11-10T01:30:00+00:00', '2026-11-11T02:00:00+00:00'],
            ]);
    });
});

describe('fromWS', function () {
    it('maps an active WS admission', function () {
        $admission = AdmissionDto::fromWS(portalFixture('ws-admission'));

        expect($admission)
            ->hn->toBe(99)
            ->an->toBe(2)
            ->status->toBe('in-progress')
            ->active->toBeTrue()
            ->attend_practitioner->toBe('นพ.เจ้าของไข้ ทดสอบ')
            ->admit_practitioner->toBeNull()
            ->discharge_practitioner->toBeNull()
            ->discharged_at->toBeNull()
            ->and($admission->admitted_at->toIso8601String())->toBe('2026-11-10T01:30:00+00:00')
            ->and($admission->locations->sole()->ward_code)->toBe('1216');
    });

    it('marks a discharged WS admission finished', function () {
        $admission = AdmissionDto::fromWS([...portalFixture('ws-admission'), 'discharged_at' => '2026-11-12 13:00:00']);

        expect($admission)
            ->status->toBe('finished')
            ->active->toBeFalse()
            ->and($admission->discharged_at->toIso8601String())->toBe('2026-11-12T06:00:00+00:00');
    });
});
