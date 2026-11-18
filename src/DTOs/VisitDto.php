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
     * Total time covered by the child encounters: the union of their start → end intervals,
     * so overlapping encounters (two clinics at once) count once and gaps between clinics
     * are not counted.
     *
     * The parent's `end` is not used: eHIS closes visits automatically just after midnight.
     * A visit without items falls back to its own start → end.
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

        $intervals = $this->items
            ->filter(fn (EncounterItemDto $item) => $item->getDurationInMinutes() !== null)
            ->map(fn (EncounterItemDto $item) => [$item->start->getTimestamp(), $item->end->getTimestamp()])
            ->sortBy(fn (array $interval) => $interval[0])
            ->values();

        if ($intervals->isEmpty()) {
            return null;
        }

        $seconds = 0;
        [$start, $end] = $intervals->first();
        foreach ($intervals->slice(1) as [$nextStart, $nextEnd]) {
            if ($nextStart > $end) {
                $seconds += $end - $start;
                [$start, $end] = [$nextStart, $nextEnd];
            } else {
                $end = max($end, $nextEnd);
            }
        }
        $seconds += $end - $start;

        return intdiv($seconds, 60);
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
