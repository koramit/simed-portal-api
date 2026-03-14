<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;

readonly class PatientDto
{
    public function __construct(
        public bool $alive,
        public ?Carbon $date_death,
        public int $hn,
        public string $patient_name,
        public string $title,
        public string $first_name,
        public ?string $middle_name,
        public string $last_name,
        public ?string $document_id,
        public ?Carbon $dob,
        public string $gender,
        public ?string $race,
        public ?string $nation,
        public ?string $tel_no,
        public ?string $spouse,
        public ?string $address,
        public ?string $subdistrict,
        public ?string $district,
        public ?string $postcode,
        public ?string $province,
        public ?string $insurance_name,
        public ?string $marital_status,
        public ?string $alternative_contact,
        public array $photo = [],
    ) {}

    public static function fromDSL(array $data): static
    {
        return new static(
            $data['alive'],
            $data['date_death'] ? Carbon::create($data['date_death']) : null,
            $data['hn'],
            $data['patient_name'],
            $data['title'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['document_id'],
            $data['dob'] ? Carbon::create($data['dob']) : null,
            $data['gender'],
            $data['race'],
            $data['nation'],
            $data['tel_no'],
            $data['spouse'],
            $data['address'],
            $data['subdistrict'],
            $data['district'],
            $data['postcode'],
            $data['province'],
            $data['insurance_name'],
            $data['marital_status'],
            $data['alternative_contact'],
            $data['photo'] ?? [],
        );
    }

    public static function fromWS(array $data): static
    {
        return new static(
            $data['alive'],
            $data['date_death'] ?? null
                ? Carbon::create($data['date_death'])
                : null,
            $data['hn'],
            $data['patient_name'],
            $data['title'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['document_id'],
            $data['dob'] ? Carbon::create($data['dob']) : null,
            $data['gender'],
            $data['race'],
            $data['nation'],
            $data['tel_no'],
            $data['spouse'],
            $data['address'],
            $data['subdistrict'],
            $data['district'],
            $data['postcode'],
            $data['province'],
            $data['insurance_name'],
            $data['marital_status'],
            $data['alternative_contact'],
            $data['photo'] ?? [],
        );
    }
}
