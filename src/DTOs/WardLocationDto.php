<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

readonly class WardLocationDto
{
    public function __construct(
        public string $ward_code,
        public string $ward_name,
        public ?string $bed = null,
        public ?string $room = null,
        public ?Carbon $start = null,
        public ?Carbon $end = null,
    ) {}

    public static function fromDSL(array $data): static
    {
        $codes = explode('-', str_replace('Location/', '', $data['location']['reference']));
        $bed = $codes[0];
        $room = $codes[1];
        $ward_code = $codes[2];
        $wardNames = explode('-', $data['location']['display']);
        $wardName = count($wardNames) > 3
            ? "$wardNames[2]-$wardNames[3]"
            : $wardNames[2];
        $ward_name = Str::replaceFirst('หอผู้ป่วย', '', $wardName);
        $start = Carbon::create($data['period']['start'])->timezone('UTC');
        $end = $data['period']['end'] ?? null
            ? Carbon::create($data['period']['end'])->timezone('UTC')
            : null;

        return new static($ward_code, $ward_name, $bed, $room, $start, $end);
    }
}
