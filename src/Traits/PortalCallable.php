<?php

namespace Koramit\SiMEDPortalAPI\Traits;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Koramit\SiMEDPortalAPI\DTOs\ResponseDto;

trait PortalCallable
{
    protected function makePost(string $route, array $data): ResponseDto
    {
        try {
            $response = Http::withToken(config('simed-portal.token'))
                ->retry(3, 250, function (Exception $exception) {
                    return $exception instanceof ConnectionException
                        && str_contains($exception->getMessage(), 'timed out after');
                }, throw: false)
                ->withOptions(['verify' => config('simed-portal.verify_ssl')])
                ->acceptJson()
                ->post(config('simed-portal.endpoint').$route, $data);
        } catch (Exception $e) {
            Log::error($route.'@portal '.$e->getMessage());

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
