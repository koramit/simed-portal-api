<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class VisitDto
{
    /** @param Collection<EncounterItemDto> $items */
    public function __construct(
        public string $vn,
        public string $hn,
        public string $patient_name,
        public Carbon $visited_at,
        public string $type,
        public string $status,
        public Collection $items,
    ) {}

    public function countDistinctClinics(): int
    {
        return collect($this->items)->pluck('clinicCode')->unique()->count();
    }

    public static function fromFHIRBundle(array $bundle): VisitDto
    {
        $entries = collect($bundle['response']['entry']);

        $parentEntry = $entries->first(fn ($e) => ! isset($e['resource']['partOf']));
        $res = $parentEntry['resource'];

        $items = $entries->filter(fn ($e) => isset($e['resource']['partOf']))
            ->map(function ($e) {
                $r = $e['resource'];

                return new EncounterItemDto(
                    id: $r['id'],
                    clinic_code: $r['serviceType']['coding'][0]['code'] ?? 'N/A',
                    clinic_name: $r['serviceType']['coding'][0]['display'] ?? 'Unknown',
                    location: $r['location'][0]['location']['display'] ?? '',
                    department: $r['serviceProvider']['display'] ?? '',
                    doctor: $r['participant'][0]['individual']['display'] ?? 'ไม่ระบุแพทย์',
                    start: Carbon::create($r['period']['start'])->timezone('UTC'),
                    end: isset($r['period']['end']) ? Carbon::create($r['period']['end'])->timezone('UTC') : null,
                    status: $r['status']
                );
            })
            ->sortBy(fn ($item) => $item->start->getTimestamp())
            ->values();

        return new VisitDto(
            vn: $res['id'],
            hn: $res['subject']['identifier']['value'],
            patient_name: $res['subject']['display'],
            visited_at: Carbon::create($res['period']['start'])->timezone('UTC'),
            type: $res['type'][0]['coding'][0]['display'] ?? '',
            status: $res['status'],
            items: $items
        );
    }
}
