<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file manages telemetry settings for the unified
    | logging framework, including request tracking, user context, and
    | distributed tracing capabilities.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Telemetry
    |--------------------------------------------------------------------------
    |
    | This option determines if telemetry context (request ID, user ID,
    | session ID, trace ID) should be automatically added to log entries.
    |
    */

    'enabled' => env('TELEMETRY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Request ID Header
    |--------------------------------------------------------------------------
    |
    | The HTTP header name used to pass request IDs between services.
    | This enables correlation of logs across distributed systems.
    |
    */

    'request_id_header' => env('TELEMETRY_REQUEST_ID_HEADER', 'X-Request-ID'),

    /*
    |--------------------------------------------------------------------------
    | Trace ID Header
    |--------------------------------------------------------------------------
    |
    | The HTTP header name used for distributed tracing. This allows
    | tracking requests across multiple services and systems.
    |
    */

    'trace_id_header' => env('TELEMETRY_TRACE_ID_HEADER', 'X-Trace-ID'),

    /*
    |--------------------------------------------------------------------------
    | Include User Context
    |--------------------------------------------------------------------------
    |
    | Determines if user information should be included in telemetry data.
    | This adds user_id to logs when a user is authenticated.
    |
    */

    'include_user_context' => env('TELEMETRY_INCLUDE_USER', true),

    /*
    |--------------------------------------------------------------------------
    | Include Session Context
    |--------------------------------------------------------------------------
    |
    | Determines if session information should be included in telemetry data.
    | This adds session_id to logs when a session is active.
    |
    */

    'include_session_context' => env('TELEMETRY_INCLUDE_SESSION', true),

    /*
    |--------------------------------------------------------------------------
    | Telemetry Sampling Rate
    |--------------------------------------------------------------------------
    |
    | The percentage of requests to include full telemetry data for.
    | Value should be between 0 and 100. Use 100 for all requests.
    | Lower values can reduce log volume in high-traffic scenarios.
    |
    */

    'sampling_rate' => env('TELEMETRY_SAMPLING_RATE', 100),

];
