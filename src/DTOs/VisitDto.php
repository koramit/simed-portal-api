<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class VisitDto
{
    /** FHIR Encounter statuses that mean the encounter is not over yet. */
    public const ACTIVE_STATUSES = ['planned', 'arrived', 'triaged', 'in-progress', 'onleave'];

    /** @param Collection<EncounterItemDto> $items */
    public function __construct(
        public string $vn,
        public string $hn,
        public string $patient_name,
        public Carbon $start,
        public ?Carbon $end,
        public ?string $type,
        public string $status,
        public bool $active,
        public Collection $items,
    ) {}

    public function countDistinctClinics(): int
    {
        return collect($this->items)->pluck('clinic_code')->unique()->count();
    }

    /**
     * Sum of the child encounters' own durations (time between clinics is not counted).
     *
     * Neither the parent's `end` (may be an automatic midnight close) nor the latest item `end`
     * (may be a late chart edit, days later) is a reliable departure time, so the visit span is
     * not used. A visit without items falls back to its own start → end.
     * Null while the visit is still active, or when no item has a duration.
     */
    public function getDurationInMinutes(): ?int
    {
        if ($this->active) {
            return null;
        }

        if ($this->items->isEmpty()) {
            return $this->end ? (int) $this->start->diffInMinutes($this->end) : null;
        }

        $durations = $this->items
            ->map(fn (EncounterItemDto $item) => $item->getDurationInMinutes())
            ->filter(fn (?int $minutes) => $minutes !== null);

        return $durations->isEmpty() ? null : (int) $durations->sum();
    }

    /**
     * Null when the bundle has no parent visit (an entry without `partOf`),
     * e.g. an empty bundle or one holding only child encounters.
     */
    public static function fromFHIRBundle(array $bundle): ?VisitDto
    {
        $entries = collect($bundle['response']['entry'] ?? []);

        $res = $entries->first(fn ($e) => ! isset($e['resource']['partOf']))['resource'] ?? null;
        if ($res === null) {
            return null;
        }

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
            start: Carbon::create($res['period']['start'])->timezone('UTC'),
            end: isset($res['period']['end']) ? Carbon::create($res['period']['end'])->timezone('UTC') : null,
            type: $res['type'][0]['coding'][0]['display'] ?? null,
            status: $res['status'],
            active: in_array($res['status'], self::ACTIVE_STATUSES, true),
            items: $items
        );
    }
}
