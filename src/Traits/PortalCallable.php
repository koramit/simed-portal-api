<?php

namespace Koramit\SiMEDPortalAPI\Traits;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;
use Koramit\SiMEDPortalAPI\Exceptions\PortalNotConfiguredException;
use Throwable;

trait PortalCallable
{
    protected function makePost(string $route, array $data): ResponseDto
    {
        $request = $this->portalRequest(Http::createPendingRequest());
        $url = $this->portalUrl($route);

        try {
            $response = $request->post($url, $this->normalisePayload($data));
        } catch (Exception $e) {
            return $this->toResponseDto($route, $e);
        }

        return $this->toResponseDto($route, $response);
    }

    /**
     * Send several posts concurrently in-process (Http::pool), with the same token, timeouts
     * and retries as makePost.
     *
     * @param  array<string, array{0: string, 1: array}>  $calls  key => [route, data]
     * @return array<string, ResponseDto> same keys
     */
    protected function makePostPool(array $calls): array
    {
        $urls = array_map(fn (array $call) => $this->portalUrl($call[0]), $calls);
        // Fail on bad config before sending anything (inside the pool it would be caught as a 503).
        $this->portalToken();
        $this->portalVerify();

        try {
            $results = Http::pool(function (Pool $pool) use ($calls, $urls) {
                foreach ($calls as $key => [, $data]) {
                    $this->portalRequest($pool->as((string) $key))->post($urls[$key], $this->normalisePayload($data));
                }
            });
        } catch (Exception $e) {
            $results = array_fill_keys(array_keys($calls), $e);
        }

        $dtos = [];
        foreach ($calls as $key => [$route]) {
            $dtos[$key] = $this->toResponseDto($route, $results[$key] ?? null);
        }

        return $dtos;
    }

    /** Payload keys holding identifiers; callers may pass them as string or int, the Portal gets strings. */
    protected const IDENTIFIER_KEYS = ['hn', 'an', 'number'];

    /** Send identifiers (hn, an, ward number) as strings, whatever type the caller passed. */
    protected function normalisePayload(array $data): array
    {
        foreach (self::IDENTIFIER_KEYS as $key) {
            if (is_int($data[$key] ?? null)) {
                $data[$key] = (string) $data[$key];
            }
        }

        return $data;
    }

    /** Apply the Portal token, timeouts, retries and TLS options to a request. */
    protected function portalRequest(PendingRequest $request): PendingRequest
    {
        // Retry only connection errors and timeouts; a received response (any status) is final.
        return $request->withToken($this->portalToken())
            ->timeout((float) config('simed-portal.timeout', 10))
            ->connectTimeout((float) config('simed-portal.connect_timeout', 3))
            ->retry(
                max(1, (int) config('simed-portal.retry_times', 3)),
                (int) config('simed-portal.retry_delay_ms', 250),
                fn (Throwable $exception) => $exception instanceof ConnectionException,
                throw: false,
            )
            ->withOptions(['verify' => $this->portalVerify()])
            ->acceptJson();
    }

    /** Turn a Portal response, or the exception that replaced it, into a ResponseDto. */
    protected function toResponseDto(string $route, Response|Throwable|null $response): ResponseDto
    {
        if (! $response instanceof Response) {
            Log::error($route.'@portal '.($response?->getMessage() ?? 'no response'));

            return new ResponseDto(
                ok: false,
                found: false,
                status: 503,
                message: config('simed-portal.service_failed_message'),
                data: []
            );
        }

        if ($response->successful() && ($response->json()['ok'] ?? false)) {
            $data = $response->json();

            // The Portal is a proxy: an upstream non-200 comes back as
            // {ok: true, found: false, status, body}, so a `status` key means the upstream failed.
            if (isset($data['status'])) {
                Log::notice($route.'@portal upstream '.$data['status'].' '.($data['body'] ?? ''));

                return new ResponseDto(
                    ok: false,
                    found: false,
                    status: (int) $data['status'],
                    message: config('simed-portal.service_failed_message'),
                    data: []
                );
            }

            return new ResponseDto(
                ok: true,
                found: $data['found'] ?? false,
                status: $response->status(),
                message: $data['message'] ?? null,
                data: $this->trimUnuseKeys($data)
            );
        }

        Log::notice($route.'@portal '.$response->body());

        // Host apps must validate input before calling the Portal, so a 422 here is a host-app bug:
        // fail loudly with the Portal's field errors ({"message": ..., "errors": {"hn": [...]}}).
        if ($response->status() === 422) {
            throw ValidationException::withMessages($response->json('errors') ?? []);
        }

        return new ResponseDto(
            ok: false,
            found: false,
            status: $response->status(),
            message: config('simed-portal.service_failed_message'),
            data: []
        );
    }

    /**
     * Guzzle `verify` option from `simed-portal.verify_ssl`: a boolean (also "1"/"0", "yes"/"no", "on"/"off")
     * or the path of a CA bundle, which must exist.
     */
    protected function portalVerify(): bool|string
    {
        $verify = config('simed-portal.verify_ssl', true);
        if (is_bool($verify)) {
            return $verify;
        }

        $verify = trim((string) $verify);
        if ($verify === '') {
            return true;
        }

        $flag = filter_var($verify, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($flag !== null) {
            return $flag;
        }

        if (! is_file($verify) || ! is_readable($verify)) {
            throw new PortalNotConfiguredException(
                "SiMED Portal CA bundle not found or not readable: {$verify} (SIMED_PORTAL_VERIFY_SSL)."
            );
        }

        return $verify;
    }

    protected function portalToken(): string
    {
        $token = (string) config('simed-portal.token');
        if ($token === '') {
            throw PortalNotConfiguredException::missing('token', 'SIMED_PORTAL_TOKEN');
        }

        return $token;
    }

    /** Join the endpoint and route with exactly one `/`, whether or not the endpoint ends with one. */
    protected function portalUrl(string $route): string
    {
        $endpoint = rtrim((string) config('simed-portal.endpoint'), '/');
        if ($endpoint === '') {
            throw PortalNotConfiguredException::missing('endpoint', 'SIMED_PORTAL_ENDPOINT');
        }

        return $endpoint.'/'.ltrim($route, '/');
    }

    protected function trimUnuseKeys(array $data): array
    {
        unset(
            $data['ok'],
            $data['found'],
            $data['status'],
            $data['error'],
            $data['message'],
            $data['body'],
        );

        return $data;
    }
}
