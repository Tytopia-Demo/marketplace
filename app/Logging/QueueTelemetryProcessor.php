<?php

namespace App\Logging;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class QueueTelemetryProcessor
{
    /**
     * Register queue event listeners to preserve telemetry context.
     *
     * @return void
     */
    public static function register(): void
    {
        // Set request ID for queue jobs
        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            $payload = $event->job->payload();

            // Try to get request ID from job payload
            $requestId = $payload['data']['request_id'] ??
                         $payload['request_id'] ??
                         null;

            // Generate new request ID if not available
            if (! $requestId) {
                $requestId = Str::uuid()->toString();
            }

            // Store request ID globally
            app()->instance('request.id', $requestId);

            if (! defined('REQUEST_ID')) {
                define('REQUEST_ID', $requestId);
            }
        });
    }

    /**
     * Add telemetry context to job payload.
     *
     * @param  array  $payload
     * @return array
     */
    public static function addTelemetryToPayload(array $payload): array
    {
        $telemetry = [
            'request_id' => get_request_id() ?: Str::uuid()->toString(),
        ];

        if (auth()->check()) {
            $telemetry['user_id'] = auth()->id();
        }

        if (session()->isStarted()) {
            $telemetry['session_id'] = session()->getId();
        }

        $payload['telemetry'] = $telemetry;

        return $payload;
    }
}
