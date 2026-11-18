<?php

return [
    'endpoint' => env('SIMED_PORTAL_ENDPOINT'),
    'token' => env('SIMED_PORTAL_TOKEN'),
    // TLS verification of the Portal's certificate. AD passwords and patient data go over this connection.
    // - true (default): verify against the system CA store.
    // - a file path: verify against that CA bundle (e.g. the hospital's internal CA), /path/to/ca.pem.
    // - false: no verification at all. Local development only.
    'verify_ssl' => env('SIMED_PORTAL_VERIFY_SSL', true),
    // Seconds to wait for a response / for the connection. Worst case per call is about
    // retry_times × timeout (3 × 10s ≈ 30s, the same as one call with Laravel's default).
    'timeout' => env('SIMED_PORTAL_TIMEOUT', 10),
    'connect_timeout' => env('SIMED_PORTAL_CONNECT_TIMEOUT', 3),
    // Total attempts on a connection error or timeout (1 = no retry), and the pause between them.
    'retry_times' => env('SIMED_PORTAL_RETRY_TIMES', 3),
    'retry_delay_ms' => env('SIMED_PORTAL_RETRY_DELAY_MS', 250),
    'service_failed_message' => env('SIMED_PORTAL_SERVICE_FAILED_MESSAGE', 'Service unavailable at the moment, please try again shortly.'),
];
