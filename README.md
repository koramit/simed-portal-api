# SiMED Portal API for Laravel

A Laravel client for the SiMED Portal API: patients, admissions, OPD visits, ward lists, AD users and lab results.

The Portal is a **proxy** to the hospital services (WS, eHIS DSL/FHIR, AD, lab). Every call is a `POST` to one Portal route with a bearer token. A call either reaches the Portal and its upstream service, or it fails. The package returns failures as values and throws only for host-app bugs (see [Errors](#errors-and-what-throws)).

- PHP `^8.3`, Laravel `^12.71|^13.0`.
- There are two surfaces:
  - **`SiMEDPortalUtil` facade** (typed): returns DTOs. Use it in new code.
  - **`*API` classes** (low level): return `ResponseDto` with the Portal's raw `array` data. They cover every route, including those with no typed method (lab, transfers, patient admission lists, raw FHIR searches).

## Installation

```bash
composer require koramit/simed-portal-api
```

Laravel's package discovery registers the service provider (`Koramit\SiMEDPortalAPI\PortalAPIServiceProvider`) and the facade alias `SiMEDPortalUtil`. You don't need to add anything to `bootstrap/providers.php` or `config/app.php`.

Optionally, publish the config file:

```bash
php artisan vendor:publish --tag=simed-portal-config
```

This writes `config/simed-portal.php`. You don't need it: unset keys fall back to the package defaults, and that includes keys added in later versions to a config file you published earlier.

## Configuration

Set at least the endpoint and the token in `.env`. All keys are under `config('simed-portal.*')`.

| Env var | Config key | Default | Meaning |
|---|---|---|---|
| `SIMED_PORTAL_ENDPOINT` | `endpoint` | none, **required** | Portal API base URL, e.g. `https://portal.example/api`. A trailing `/` is optional. |
| `SIMED_PORTAL_TOKEN` | `token` | none, **required** | Portal (Sanctum) token. Its abilities decide which calls work (see [Token abilities](#token-abilities)). |
| `SIMED_PORTAL_VERIFY_SSL` | `verify_ssl` | `true` | TLS verification. `true` checks against the system CA store. A file path checks against that CA bundle (e.g. the hospital's internal CA). `false` turns verification off and is for local development only. `1`/`0`, `yes`/`no` and `on`/`off` are accepted. |
| `SIMED_PORTAL_TIMEOUT` | `timeout` | `10` | Seconds to wait for a response. |
| `SIMED_PORTAL_CONNECT_TIMEOUT` | `connect_timeout` | `3` | Seconds to wait for the connection. |
| `SIMED_PORTAL_RETRY_TIMES` | `retry_times` | `3` | Total attempts on a connection error or timeout (`1` = no retry). A response that arrives, whatever its status, is never retried. The worst case per call is about `retry_times × timeout`. |
| `SIMED_PORTAL_RETRY_DELAY_MS` | `retry_delay_ms` | `250` | Pause between attempts. |
| `SIMED_PORTAL_TIMEZONE` | `timezone` | `Asia/Bangkok` | Timezone of the Portal's date strings that carry no offset (see [Dates and timezones](#dates-and-timezones)). |
| `SIMED_PORTAL_SERVICE_FAILED_MESSAGE` | `service_failed_message` | `Service unavailable at the moment, please try again shortly.` | The `message` of every failed (`ok: false`) result. You can show it to users. |

An empty `endpoint` or `token`, or a `verify_ssl` path that is not a readable file, throws `PortalNotConfiguredException` on the first call.

## The result model

Every typed call returns `ok` and `found`:

| `ok` | `found` | Meaning | `data` / DTO |
|---|---|---|---|
| `false` | `false` | The Portal or its upstream service failed (connection error, timeout, HTTP error, upstream non-200, missing token ability). `message` is `service_failed_message`. The details go to the log. | `null` |
| `true` | `false` | The call worked, but there is no such record (or the list is empty). | `null` |
| `true` | `true` | Found. | the DTO, or a `Collection` of DTOs for list calls |

Branch on `ok` first: `found: false` means "does not exist" only when `ok` is `true`.

```php
use Koramit\SiMEDPortalAPI\Facades\SiMEDPortalUtil;

$result = SiMEDPortalUtil::getPatientWS('12345678');

if (! $result->ok) {
    return back()->with('error', $result->message); // Portal down: try again later
}
if (! $result->found) {
    abort(404); // no such HN
}

$patient = $result->data; // PatientDto
```

List calls return `found: false` and `data: null` for an empty list, **not** an empty collection. Use `$result->data ?? collect()` if you want a collection either way.

## Errors and what throws

| Situation | Behaviour |
|---|---|
| Connection error or timeout (after retries) | `ok: false`, `ResponseDto::$status` `503`, logged with `Log::error`. |
| Portal HTTP error (401 bad token, 403 missing ability, 404, 5xx) | `ok: false`, `$status` = the HTTP status, logged with `Log::notice`. |
| Upstream failure reported by the Portal (`{ok: true, status: …}`) | `ok: false`, `$status` = the upstream status, logged with `Log::notice`. |
| Portal **422** (input failed the Portal's validation) | **Throws** `Illuminate\Validation\ValidationException` with the Portal's field names (`hn`, `an`, `key_value`, `login`, `number`, …). |
| `endpoint` / `token` empty, or `verify_ssl` names a missing CA file | **Throws** `Koramit\SiMEDPortalAPI\Exceptions\PortalNotConfiguredException` before sending anything. |
| `EncounterAPI::getEncounter()` called with no argument at all | No request is sent. Returns `ResponseDto(ok: false, status: 422, message: 'No parameter provided.')` and does not throw. |

**Validate input in the host app before calling.** A 422 is treated as a host-app bug. In a web request, the `ValidationException` redirects back with the Portal's field names, which may not match your form. The Portal's rules:

| Input | Portal rule |
|---|---|
| `hn`, `an` | exactly 8 digits (`digits:8`) |
| `getPatientDSL` key name / key value | `hn`, `cid` or `passport` / `alpha_num`, at most 13 characters |
| user `org_id` | 8 digits. Any other `$id` is sent as a `login`. |
| ward number | an existing Portal ward id (`AdmissionWardSummaryDto::$portal_id`). **Not** the eHIS ward number (`ws_id`). |
| dates (`begin_date`, `end_date`, `date_start`, `date_end`) | any string Laravel's `date` rule accepts, e.g. `2026-11-17` |

Identifiers can be passed as `string` or `int`. They are always sent as strings, so leading zeros only survive if you pass a string.

## Typed API: `SiMEDPortalUtil`

Import the facade class `Koramit\SiMEDPortalAPI\Facades\SiMEDPortalUtil`, or use the global alias `\SiMEDPortalUtil`. It resolves the `simed-portal-util` singleton (`Koramit\SiMEDPortalAPI\PortalUtilManager`). All DTOs are `readonly` classes in `Koramit\SiMEDPortalAPI\DTOs` with public typed properties. All dates are `Illuminate\Support\Carbon`.

`$withSensitiveData` defaults to `true` everywhere: host apps are clinical and need identifying data. With `false` the call goes to the route without `/sd`. The Portal then returns the same keys with masked values, so the DTOs still build.

| Method | Returns | `data` | Portal route |
|---|---|---|---|
| `getPatientWS(string\|int $hn, bool $withSensitiveData = true)` | `PortalResultDto` | `PatientDto` | `pt[/sd]` |
| `getPatientDSL(string $keyValue, string $keyName = 'hn', bool $withSensitiveData = true)` | `PortalResultDto` | `PatientDto` | `dsl/pt[/sd]` |
| `getUser(string\|int $id)` | `PortalResultDto` | `ADUserDto` | `user` |
| `authenticate(string $login, string $password)` | `PortalResultDto` | `ADUserDto` | `authenticate` |
| `getAdmissionWS(string\|int $an, bool $withSensitiveData = true)` | `PortalResultDto` | `AdmissionDto` | `adm[/sd]` |
| `getAdmissionDSL(string\|int $an)` | `PortalResultDto` | `AdmissionDto` | `en` |
| `getWardActiveAdmissions(string\|int $wardNumber)` | `PortalResultDto` | `Collection<int, AdmissionSummaryDto>` | `w/adm` |
| `getActiveAdmissionWards()` | `PortalResultDto` | `Collection<int, AdmissionWardSummaryDto>` | `w/adm` |
| `getDischargedAdmissions(string\|int $wardNumber, string $beginDate, ?string $endDate = null)` | `PortalResultDto` | `Collection<int, AdmissionSummaryDto>` | `adm/dc` |
| `getDischargedAdmissionWards(string $beginDate, ?string $endDate = null)` | `PortalResultDto` | `Collection<int, AdmissionWardSummaryDto>` | `adm/dc` |
| `getPatientLatestEncounters(string\|int $hn)` | `LatestEncountersDto` | (see below) | `en` ×2, concurrent |
| `getVisitBundle(string\|int $vn)` | `VisitBundleDto` | (see below) | `en` |

### Patients

```php
$result = SiMEDPortalUtil::getPatientDSL('1234567890123', 'cid'); // or 'hn', 'passport'
$patient = $result->data;
$patient->hn;                       // int
$patient->dob?->toDateString();     // '1980-05-02' (calendar date, see Dates)
$patient->alive;                    // bool
```

`PatientDto`: `bool $alive`, `?Carbon $date_death`, `int $hn`, `string $patient_name`, `string $title`, `string $first_name`, `?string $middle_name`, `string $last_name`, `?string $document_id`, `?Carbon $dob`, `string $gender`, `?string $race`, `?string $nation`, `?string $tel_no`, `?string $spouse`, `?string $address`, `?string $subdistrict`, `?string $district`, `?string $postcode`, `?string $province`, `?string $insurance_name`, `?string $marital_status`, `?string $alternative_contact`, `array $photo`.

WS (`getPatientWS`) and DSL (`getPatientDSL`) patients have the same shape. Only DSL can look up by `cid` or `passport`.

### AD users

```php
$result = SiMEDPortalUtil::authenticate($request->login, $request->password);
if ($result->ok && $result->found) {
    $user = $result->data; // ADUserDto
}
```

- `getUser($id)`: an all-digit `$id` is looked up as an `org_id`, anything else as a `login`.
- `authenticate()`: a rejected login doesn't throw, so check `ok && found` before using `data`. Validate that `login` and `password` are present first, or the Portal's 422 throws.
- Known Portal-side issue: when the AD service fails and the Portal falls back to the ALT service, and ALT answers 400, the Portal reports `found: true` with no `org_id`. `ADUserDto` then throws a `TypeError`.

`ADUserDto`: `string $login`, `string $org_id`, `string $full_name`, `?string $full_name_en`, `?string $document_id`, `?int $position_id`, `?string $position_name`, `?int $division_id`, `?string $division_name`, `?string $department_name`, `?string $office_name`, `?string $email`, `int $password_expires_in_days`, `?string $remark`.

The Portal sends a different subset per path, and missing optional fields are `null`. A lookup by login has no `full_name_en`, `department_name`, `office_name` or `email`. The ALT authentication fallback sends only `login`, `org_id`, `full_name`, `email` and `password_expires_in_days`.

### Admissions

```php
$admission = SiMEDPortalUtil::getAdmissionDSL('66012345')->data; // AdmissionDto
$admission->attend_practitioner;   // the doctor responsible for the patient
$admission->locations->first();    // current (or last) ward, WardLocationDto
$admission->active;                // true while not discharged
```

`AdmissionDto`: `int $hn`, `string $patient_name`, `int $an`, `Carbon $admitted_at`, `Collection<WardLocationDto> $locations`, `?string $status`, `?string $admit_practitioner`, `?string $attend_practitioner`, `?string $discharge_practitioner`, `?Carbon $discharged_at`, `?string $discharge_status`, `?string $discharge_type`, `bool $active`.

The fields mean the same whichever source built the DTO:

| Field | DSL (`getAdmissionDSL`, eHIS FHIR) | WS (`getAdmissionWS`) |
|---|---|---|
| `attend_practitioner` | the "แพทย์เจ้าของไข้" participant, or the admitting doctor until one is recorded | `attending`, WS's single doctor (the admitting doctor early on, the discharging doctor after D/C) |
| `admit_practitioner` | the admitting doctor | always `null` |
| `discharge_practitioner` | the "แพทย์จำหน่าย" participant | always `null` |
| `status` | FHIR status (`in-progress`, `finished`, …) | `finished` when discharged, else `in-progress` |
| `locations` | every ward stay, newest first. Consecutive stays in the same ward are merged, and the zero-length entry eHIS adds at discharge is dropped. | one entry: the current ward, with no bed, room or dates |

Clinical apps should read `attend_practitioner`.

`WardLocationDto`: `string $ward_code`, `string $ward_name` (with the "หอผู้ป่วย" prefix removed), `?string $bed`, `?string $room`, `?Carbon $start`, `?Carbon $end`, `?string $ward_name_short` (WS only).

### Ward lists

```php
// 1. Which wards have patients right now?
$wards = SiMEDPortalUtil::getActiveAdmissionWards()->data ?? collect();
// 2. Who is in one of them? Pass the Portal ward id, not ws_id.
$patients = SiMEDPortalUtil::getWardActiveAdmissions($wards->first()->portal_id)->data ?? collect();

// Discharged from one ward on 17 Nov 2026 (Portal local time):
SiMEDPortalUtil::getDischargedAdmissions($portalId, '2026-11-17');
// Wards with discharges in a range, with counts:
SiMEDPortalUtil::getDischargedAdmissionWards('2026-11-01', '2026-11-08');
```

- The ward argument is the Portal's ward id (`AdmissionWardSummaryDto::$portal_id`). A ward id that doesn't exist fails the Portal's validation, so it throws.
- Discharge window: `discharged_at` between `$beginDate` and `$endDate`, both ends **inclusive**. `$endDate` defaults to `$beginDate` + 1 day, so a bare date covers that day from midnight to the next midnight. Dates are read as Portal local time (Bangkok). Pass plain `Y-m-d` or `Y-m-d H:i:s` strings with no offset.

`AdmissionSummaryDto`: `int $an`, `int $hn`, `string $name`, `string $gender`, `?int $age`, `?string $age_unit` (both `null` when the Portal has no date of birth), `Carbon $admitted_at`, `?string $discharge_status`, `?string $discharge_type`, `?Carbon $discharged_at`. The last three are only set by the discharge calls.

`AdmissionWardSummaryDto`: `int $portal_id`, `?string $ws_id` (the eHIS ward number, as stored), `string $name`, `string $name_short`, `int $admissions_count`.

### Latest encounters and OPD visits

```php
$latest = SiMEDPortalUtil::getPatientLatestEncounters('12345678'); // LatestEncountersDto
$latest->ok;        // false if either search failed; the half that succeeded is still filled
$latest->message;   // set when ok is false
$latest->admission; // ?AdmissionDto, newest IPD admission (DSL)
$latest->visit;     // ?VisitDto, newest OPD visit with its clinic encounters

$bundle = SiMEDPortalUtil::getVisitBundle('12345');   // VisitBundleDto {ok, found, message, ?visit}
```

- `getPatientLatestEncounters` runs both FHIR searches (class `IMP` and `AMB`, sorted `-date`, 1 result) concurrently with `Http::pool()`. A `null` `admission` or `visit` means "none" only when `ok` is `true`.
- `getVisitBundle` searches by VN. A bundle without a parent visit counts as `found: false`.

`VisitDto`: `string $vn`, `string $hn`, `string $patient_name`, `Carbon $start`, `?Carbon $end`, `?string $type`, `string $status`, `bool $active`, `Collection<EncounterItemDto> $items` (the clinic encounters, sorted by start).
- `active`: the status is one of `VisitDto::ACTIVE_STATUSES` (`planned`, `arrived`, `triaged`, `in-progress`, `onleave`).
- `getDurationInMinutes(): ?int`: the union of the items' start → end intervals, so overlapping clinics count once and gaps don't count. It is `null` while the visit is active or when no item has a duration. The parent's own `end` is ignored, because eHIS closes visits automatically just after midnight.
- `countDistinctClinics(): int`: distinct `clinic_code`s. Items without one are not counted.

`EncounterItemDto`: `string $id`, `?string $clinic_code`, `?string $clinic_name`, `?string $location`, `?string $department`, `?string $doctor`, `Carbon $start`, `?Carbon $end`, `string $status`, plus `isActive(): bool` and `getDurationInMinutes(): ?int` (`null` while active).
- The five nullable strings are `null` when the Portal leaves them out. Choose the display text in the host app, e.g. `$item->doctor ?? 'ไม่ระบุแพทย์'`.
- `doctor` is the first participant.

## Dates and timezones

Timestamps come back as UTC `Carbon` instances. Convert them for display, e.g. `$dto->admitted_at->timezone('Asia/Bangkok')`.

| Fields | How they are parsed |
|---|---|
| `AdmissionDto`, `WardLocationDto`, `VisitDto`, `EncounterItemDto` timestamps | A string with an offset keeps its instant. A string without one is read in `simed-portal.timezone`. Then converted to UTC. |
| `AdmissionSummaryDto::$admitted_at` / `$discharged_at` (ward lists) | The Portal sends Bangkok wall-clock time with a wrong `Z` suffix (`2026-11-17T08:21:00.000000Z` means 08:21 in Bangkok). The wall-clock time is read in `simed-portal.timezone`, then converted to UTC. |
| `PatientDto::$dob` / `$date_death` | Calendar dates (`YYYY-MM-DD`). They are midnight in the **host app's** timezone and are **not** converted to UTC, so `->toDateString()` always gives the Portal's date. |

Empty date values (`null`, `''`) give `null`.

## Token abilities

The Portal checks the token's Sanctum abilities per route. A missing ability returns `ok: false` with `ResponseDto::$status` `403`.

| Calls | Abilities |
|---|---|
| `getPatientWS`, `getPatientDSL`, `PatientAPI::*` | `patient:data`, plus `patient:sensitive-data` for `/sd` |
| `getAdmissionWS`, `AdmissionAPI::getAdmissionWS` / `getAdmissionEHIS` | `admission:data`, plus `patient:sensitive-data` for `/sd` |
| `AdmissionAPI::getPatientAdmissionsWS` / `getPatientAdmissionsEHIS` | `patient:admissions`, plus `patient:sensitive-data` for `/sd` |
| `AdmissionAPI::getPatientLatestAdmissionWS` / `getPatientLatestAdmissionEHIS` | `patient:recently-admission`, plus `patient:sensitive-data` for `/sd` |
| `getAdmissionDSL`, `getPatientLatestEncounters`, `getVisitBundle`, `EncounterAPI::*`, `AdmissionAPI::*DSL` | `encounter:results` |
| Ward lists, `AdmissionWardAPI::*` | `ward:admissions` |
| `getUser` | `user:data` |
| `authenticate` | `user:authenticate` |
| `LabAPI::getLabPendingReports` | `lab:pending` |
| other `LabAPI::*` | `lab:results` |

## Low-level API: `*API` classes

Instantiate them directly (`new LabAPI`). Each method sends one request and returns `Koramit\SiMEDPortalAPI\DTOs\ResponseDto`:

```php
readonly class ResponseDto {
    public bool $ok;          // same meaning as above
    public bool $found;
    public int $status;       // HTTP status; 503 when no response; the upstream status on an upstream failure
    public ?string $message;
    public array $data;       // the Portal's JSON body without ok/found/status/error/message/body; [] when !ok
}
```

Errors are handled as in [Errors](#errors-and-what-throws): a 422 throws, and everything else is a value.

### `PatientAPI`

| Method | Route | Payload |
|---|---|---|
| `getPatientWS(string\|int $hn, bool $withSensitiveData = true)` | `pt[/sd]` | `hn` |
| `getPatientDSL(string $keyValue, string $keyName = 'hn', bool $raw = false, bool $withSensitiveData = true)` | `dsl/pt[/sd]` | `key_name`, `key_value`, `raw` |

### `AdmissionAPI`

| Method | Route | Payload |
|---|---|---|
| `getAdmissionWS(string\|int $an, bool $withSensitiveData = true)` | `adm[/sd]` | `an` |
| `getPatientAdmissionsWS(string\|int $hn, bool $withSensitiveData = true)` | `pt/adm[/sd]` | `hn` |
| `getPatientLatestAdmissionWS(string\|int $hn, bool $withSensitiveData = true)` | `pt/adm/lst[/sd]` | `hn` |
| `getAdmissionEHIS(string\|int $an, bool $raw = false, bool $withSensitiveData = true)` | `dsl/adm[/sd]` | `an`, `raw` |
| `getPatientAdmissionsEHIS(string\|int $hn, bool $raw = false, bool $withSensitiveData = true)` | `dsl/pt/adm[/sd]` | `hn`, `raw` |
| `getPatientLatestAdmissionEHIS(string\|int $hn, bool $raw = false, bool $withSensitiveData = true)` | `dsl/pt/adm/lst[/sd]` | `hn`, `raw` |
| `getAdmissionDSL(string\|int $an)` | `en` | `url: "AN{$an}"` |
| `getPatientAdmissionsDSL(string\|int $hn)` | `en` | FHIR search: `subject=HN…`, `class=IMP`, `_sort=date`, `_maxresults=1000` |
| `getPatientLatestAdmissionDSL(string\|int $hn)` | `en` | FHIR search: `subject=HN…`, `class=IMP`, `_sort=-date`, `_maxresults=1` |

The `*DSL` methods return FHIR: a single resource under `data['response']`, or a search bundle with `data['response']['entry'][]['resource']`. To build DTOs from them, use `AdmissionDto::fromDSL($data)` (single) or `AdmissionDto::fromDSL($entry)` (one bundle entry). For WS data, use `AdmissionDto::fromWS($data)`.

### `AdmissionWardAPI`

| Method | Route | Payload |
|---|---|---|
| `getActiveAdmissions(string\|int\|null $wardNumber = null)` | `w/adm` | `number` (`null` = per-ward counts) |
| `getDischargeAdmissions(string $beginDate, ?string $endDate = null, string\|int\|null $wardNumber = null)` | `adm/dc` | `begin_date`, `end_date`, `number` (`null` = per-ward counts) |
| `getWardTransferWS(string\|int $an)` | `adm/mv` | `an` |

`w/adm` and `adm/dc` return their rows under `data['admissions']`. `adm/mv` returns the admission's ward transfers, oldest first (`ward_name`, `ward_ref_id`, `created_at` as `Y-m-d H:i:s` local time).

### `EncounterAPI`

- `getEncounter(?hn, ?dateStart, ?dateEnd, ?status, ?id, ?partOf, ?url, ?array $request)` → route `en`. Only the arguments you give are sent. `status` is one of `finished`, `cancelled`, `in-progress`. `request` is a raw FHIR Encounter search (e.g. `['subject' => 'HN12345678', 'class' => 'AMB', '_sort' => '-date', '_maxresults' => '1']`), and `url` is a raw FHIR read (e.g. `'AN66012345'`). With no argument at all it returns a 422 `ResponseDto` without sending anything.
- `getEncounters(array $requests): array` runs several `request` searches concurrently. It takes `[key => request]` and returns `[key => ResponseDto]`.

Use `VisitDto::fromFHIRBundle($response->data)` to parse an AMB search bundle. It returns `?VisitDto`.

### `ADUserAPI`

| Method | Route | Payload |
|---|---|---|
| `getUser(string\|int $id)` | `user` | `org_id` if `$id` is all digits, else `login` |
| `authenticate(string $login, string $password)` | `authenticate` | `login`, `password` |

`ADUserDto::fromPortal($data)` builds the DTO.

### `LabAPI`

There are no typed DTOs for lab results. `data` is the Portal's lab payload as is.

| Method | Route | Payload |
|---|---|---|
| `getLabPendingReports(string\|int $hn)` | `lab-pending` | `hn` |
| `getLabRecentOrders(string\|int $hn)` | `lab-recently` | `hn` (the Portal returns up to 10 recent orders) |
| `getLabFromRefNo(int\|string $refNo)` | `lab-from-ref-no` | `ref_no` |
| `getLabFromServiceId(string\|int $hn, int\|string\|array $serviceIds, bool $latest = false, ?string $dateStart = null, ?string $dateEnd = null)` | `lab-from-service-id` | `hn`, `service_ids[]`, `latest`, `date_start`, `date_end` |
| `getLabFromItemCodes(string\|int $hn, array\|string $itemCodes, ?string $dateStart = null, ?string $dateEnd = null)` | `lab-from-item-code` | `hn`, `item_codes[]`, `date_start`, `date_end` |
| `getLabFromItemCodeAllResults(string\|int $hn, string $itemCode)` | `lab-from-item-all` | `hn`, `item_code` |

- `getLabFromServiceId`: with `$latest = false` (the package default) the Portal returns all results, grouped. Pass `true` for the latest result only. Note that the Portal's own default, when `latest` is absent, is latest only.
- The Portal applies the date range only when **both** `$dateStart` and `$dateEnd` are given. A single date is ignored.

## Testing in the host app

All calls go through Laravel's `Http` client, including the concurrent ones, so `Http::fake()` covers everything. Fake by route, and wrap responses in the Portal envelope (`ok`, `found`).

`Http::fake` patterns match the whole URL, so `*/pt/sd` also matches `dsl/pt/sd`, and `*/adm` also matches `w/adm`. Prefix the endpoint when routes share an ending:

```php
use Illuminate\Support\Facades\Http;

$portal = rtrim(config('simed-portal.endpoint'), '/');

Http::fake([
    "$portal/dsl/pt/sd" => Http::response(['ok' => true, 'found' => false]),
    // found: the Portal's fields sit next to ok/found (here: every key PatientDto::fromWS reads)
    '*/pt/sd' => Http::response(['ok' => true, 'found' => true] + $patientFields),
    // not found / empty list
    '*/w/adm' => Http::response(['ok' => true, 'found' => false, 'admissions' => []]),
    // failure → ok: false
    '*/en' => Http::response(['ok' => false], 500),
]);
```

- A 422 response throws `ValidationException`, e.g. `Http::response(['message' => '…', 'errors' => ['hn' => ['…']]], 422)`.
- Set `simed-portal.endpoint` and `simed-portal.token` in the test environment, or every call throws `PortalNotConfiguredException`.
- Set `simed-portal.retry_delay_ms` to `0` so that faked connection errors don't slow the tests down.

## Development

```bash
composer test   # Pest; the tests use synthetic fixtures and Http::preventStrayRequests()
composer lint   # Pint
```

## License

MIT
