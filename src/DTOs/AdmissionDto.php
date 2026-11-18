<?php

namespace Koramit\SiMEDPortalAPI\DTOs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Koramit\SiMEDPortalAPI\Support\PortalDate;

/**
 * Practitioner fields have one meaning whichever source built the DTO:
 *
 * - `attend_practitioner`: the doctor responsible for the admission (what clinical apps read).
 *   DSL: the "แพทย์เจ้าของไข้" participant, falling back to the admitting doctor until one is recorded.
 *   WS: `attending`, the single doctor WS reports (the admitting doctor early on, the discharging doctor after D/C).
 * - `admit_practitioner`: the admitting doctor. DSL only, always `null` from WS.
 * - `discharge_practitioner`: the discharging doctor ("แพทย์จำหน่าย"). DSL only, always `null` from WS.
 */
readonly class AdmissionDto
{
    /** @param  Collection<WardLocationDto>  $locations */
    public function __construct(
        public int $hn,
        public string $patient_name,
        public int $an,
        public Carbon $admitted_at,
        public Collection $locations,
        public ?string $status = null,
        public ?string $admit_practitioner = null,
        public ?string $attend_practitioner = null,
        public ?string $discharge_practitioner = null,
        public ?Carbon $discharged_at = null,
        public ?string $discharge_status = null,
        public ?string $discharge_type = null,
        public bool $active = false,
    ) {}

    /** eHIS participant type codes; attending and discharging doctors share a code and differ only by display. */
    protected const ADMIT_PRACTITIONER_CODE = 'AdmitPractitioner';

    protected const ATTEND_PRACTITIONER_CODE = 'AttendPractitioner';

    protected const ATTEND_PRACTITIONER_DISPLAY = 'แพทย์เจ้าของไข้';

    protected const DISCHARGE_PRACTITIONER_DISPLAY = 'แพทย์จำหน่าย';

    public static function fromDSL(array $data): static
    {
        $data = $data['response'] ?? $data['resource'];
        $hn = $data['subject']['identifier']['value'];
        $patient_name = $data['subject']['display'];
        $an = $data['identifier'][0]['value'];
        $status = $data['status'];
        $participants = $data['participant'] ?? [];
        $admit_practitioner = static::findPractitioner($participants, self::ADMIT_PRACTITIONER_CODE);
        $attend_practitioner = static::findPractitioner($participants, self::ATTEND_PRACTITIONER_CODE, self::ATTEND_PRACTITIONER_DISPLAY)
            ?? $admit_practitioner;
        $discharge_practitioner = static::findPractitioner($participants, self::ATTEND_PRACTITIONER_CODE, self::DISCHARGE_PRACTITIONER_DISPLAY);
        $admitted_at = PortalDate::utc($data['period']['start']);
        $discharged_at = PortalDate::utc($data['period']['end'] ?? null);
        $extensions = $data['hospitalization']['extension'] ?? [];
        $discharge_status = static::findExtensionDisplay($extensions, 'DischargeStatus');
        $discharge_type = static::findExtensionDisplay($extensions, 'DischargeType');

        $locations = static::mergeWardLocations(
            collect($data['location'] ?? [])->map(fn (array $location) => WardLocationDto::fromDSL($location))
        );

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
            $discharge_status,
            $discharge_type,
            $discharged_at === null,
        );
    }

    /**
     * Merge consecutive stays in the same ward into one entry, newest first.
     *
     * The merged entry spans the earliest start to the latest end and keeps
     * the latest bed/room. Zero-length entries (e.g. the one eHIS adds at
     * discharge) are dropped unless nothing else is left.
     *
     * @param  Collection<WardLocationDto>  $locations
     * @return Collection<WardLocationDto>
     */
    protected static function mergeWardLocations(Collection $locations): Collection
    {
        $withDuration = $locations->reject(
            fn (WardLocationDto $location) => $location->end !== null && $location->start?->equalTo($location->end)
        );
        if ($withDuration->isNotEmpty()) {
            $locations = $withDuration;
        }

        return $locations
            ->sortBy(fn (WardLocationDto $location) => $location->start?->getTimestamp() ?? 0)
            ->reduce(function (Collection $merged, WardLocationDto $location) {
                $previous = $merged->last();
                if ($previous?->ward_name !== $location->ward_name) {
                    return $merged->push($location);
                }

                $merged[$merged->count() - 1] = new WardLocationDto(
                    ward_code: $location->ward_code,
                    ward_name: $location->ward_name,
                    bed: $location->bed,
                    room: $location->room,
                    start: $previous->start,
                    end: $location->end,
                    ward_name_short: $location->ward_name_short,
                );

                return $merged;
            }, collect())
            ->reverse()
            ->values();
    }

    /**
     * Display name of the first participant whose type has the given code (and display, when given).
     */
    protected static function findPractitioner(array $participants, string $code, ?string $display = null): ?string
    {
        foreach ($participants as $participant) {
            foreach ($participant['type'] ?? [] as $type) {
                foreach ($type['coding'] ?? [] as $coding) {
                    if (($coding['code'] ?? null) !== $code) {
                        continue;
                    }
                    if ($display !== null && trim($coding['display'] ?? $type['text'] ?? '') !== $display) {
                        continue;
                    }

                    return $participant['individual']['display'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Coding display of the hospitalization extension whose url ends with the given name
     * (e.g. `…dsl-ex-eHIS-DischargeStatus`).
     */
    protected static function findExtensionDisplay(array $extensions, string $name): ?string
    {
        foreach ($extensions as $extension) {
            if (str_ends_with($extension['url'] ?? '', $name)) {
                return $extension['valueCodeableConcept']['coding'][0]['display'] ?? null;
            }
        }

        return null;
    }

    public static function fromWS(array $data): static
    {
        $discharged = (bool) $data['discharged_at'];

        return new static(
            hn: $data['hn'],
            patient_name: $data['patient_name'],
            an: $data['an'],
            admitted_at: PortalDate::utc($data['admitted_at']),
            locations: collect([
                new WardLocationDto(
                    ward_code: $data['ward_number'],
                    ward_name: $data['ward_name'],
                    ward_name_short: $data['ward_name_short'],
                ),
            ]),
            // WS reports a single doctor and not its role, so admit/discharge practitioners stay null.
            attend_practitioner: $data['attending'],
            discharged_at: PortalDate::utc($data['discharged_at']),
            discharge_status: $data['discharge_status'],
            discharge_type: $data['discharge_type'],
            // WS has no status; use the FHIR values fromDSL gives for the same states.
            status: $discharged ? 'finished' : 'in-progress',
            active: ! $discharged,
        );
    }
}
