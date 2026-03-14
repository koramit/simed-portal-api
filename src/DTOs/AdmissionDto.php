<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;

readonly class AdmissionDto
{
    /**
     * @param  WardLocationDto[]  $locations
     */
    public function __construct(
        public int $hn,
        public string $patient_name,
        public int $an,
        public Carbon $admitted_at,
        public array $locations,
        public ?string $status = null,
        public ?string $admit_practitioner = null,
        public ?string $attend_practitioner = null,
        public ?string $discharge_practitioner = null,
        public ?Carbon $discharged_at = null,
        public ?string $discharged_status = null,
        public ?string $discharged_type = null,
    ) {}

    public static function fromDSL(array $data): static
    {
        $hn = $data['subject']['identifier']['value'];
        $patient_name = $data['subject']['display'];
        $an = $data['identifier'][0]['value'];
        $status = $data['status'];
        $admit_practitioner = $data['participant'][0]['individual']['display'];
        $attend_practitioner = $data['participant'][1]['individual']['display'] ?? null;
        $discharge_practitioner = $data['participant'][2]['individual']['display'] ?? null;
        $admitted_at = Carbon::create($data['period']['start'])->timezone('UTC');
        $discharged_at = $data['period']['end'] ?? null
            ? Carbon::create($data['period']['end'])->timezone('UTC')
            : null;
        $discharged_status = $data['hospitalization']['extension'][0]['valueCodeableConcept']['coding'][0]['display'] ?? null;
        $discharged_type = $data['hospitalization']['extension'][1]['valueCodeableConcept']['coding'][0]['display'] ?? null;

        /** @var WardLocationDto[] $locations */
        $locations = [];
        foreach ($data['location'] as $location) {
            $wardLocation = WardLocationDto::fromDSL($location);
            if (count($locations) === 0) {
                $locations[] = $wardLocation;

                continue;
            }

            $lastIndex = count($locations) - 1;

            if ($wardLocation->ward_name !== $locations[$lastIndex]->ward_name) {
                $locations[] = $wardLocation;

                continue;
            }

            $location['period']['end'] = $locations[$lastIndex]->end?->timezone('Asia/Bangkok')->format('c');
            $locations[$lastIndex] = WardLocationDto::fromDSL($location);
        }

        return new static(
            $hn,
            $patient_name,
            $an,
            $admitted_at,
            $locations,
            $status,
            $admit_practitioner,
            $attend_practitioner,
            $discharge_practitioner,
            $discharged_at,
            $discharged_status,
            $discharged_type,
        );
    }

    public static function fromWS(array $data): static
    {
        return new static(
            hn: $data['hn'],
            patient_name: $data['patient_name'],
            an: $data['an'],
            admitted_at: Carbon::create($data['admitted_at'], 'Asia/Bangkok')->timezone('UTC'),
            locations: [new WardLocationDto($data['ward_number'], $data['ward_name'])],
            attend_practitioner: $data['attending'],
            discharged_at: $data['discharged_at']
                ? Carbon::create($data['discharged_at'], 'Asia/Bangkok')->timezone('UTC')
                : null,
            discharged_status: $data['discharged_status'],
            discharged_type: $data['discharged_type'],
        );
    }
}
