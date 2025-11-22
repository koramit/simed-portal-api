<?php

namespace Koramit\SiMEDPortalAPI\Traits;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait PortalCallable
{
    protected function makePost(string $route, array $data): array
    {
        try {
            $response = Http::withToken(config('simed-portal.token'))
                ->retry(2, 200, function (Exception $exception) {
                    return $exception instanceof ConnectionException
                        && str_contains($exception->getMessage(), 'timed out after');
                }, throw: false)
                ->withOptions(['verify' => config('simed-portal.verify_ssl')])
                ->acceptJson()
                ->post(config('simed-portal.endpoint').$route, $data);
        } catch (Exception $e) {
            Log::error($route.'@portal '.$e->getMessage());

            return [
                'ok' => false,
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
                'body' => config('simed-portal.service_failed_message'),
            ];
        }

        if ($response->successful() && ($response->json()['ok'] ?? false)) {
            return $response->json();
        }

        Log::notice($route.'@portal '.$response->body());

        if ($response->status() === 422) {
            throw ValidationException::withMessages($response->json());
        }

        return [
            'ok' => false,
            'status' => $response->status(),
            'error' => $response->serverError() ? 'server' : 'client',
            'body' => config('simed-portal.service_failed_message'),
        ];
    }
}
