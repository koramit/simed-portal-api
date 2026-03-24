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
                status: $e->getCode(),
                message: $e->getMessage(),
                data: []
            );
        }

        if ($response->successful() && ($response->json()['ok'] ?? false)) {
            $data = $response->json();

            return new ResponseDto(
                ok: true,
                found: $data['found'] ?? false,
                status: $response->status(),
                message: $data['message'] ?? $data['body'] ?? null,
                data: $this->trimUnuseKeys($data)
            );
        }

        Log::notice($route.'@portal '.$response->body());

        if ($response->status() === 422) {
            throw ValidationException::withMessages($response->json());
        }

        return new ResponseDto(
            ok: false,
            found: false,
            status: $response->status(),
            message: $response->body(),
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
