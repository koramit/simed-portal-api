<?php

return [
    'endpoint' => env('SIMED_PORTAL_ENDPOINT'),
    'token' => env('SIMED_PORTAL_TOKEN'),
    'verify_ssl' => env('SIMED_PORTAL_VERIFY_SSL', true),
    'service_failed_message' => env('SIMED_PORTAL_SERVICE_FAILED_MESSAGE', 'Service unavailable at the moment, please try again shortly.'),
];
