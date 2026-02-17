<?php

namespace App\Logging;

use Illuminate\Support\Facades\Auth;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class TelemetryProcessor implements ProcessorInterface
{
    /**
     * Add telemetry context to log records.
     *
     * @param  LogRecord  $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;

        // Add request ID from context
        if (request()->hasHeader('X-Request-ID')) {
            $extra['request_id'] = request()->header('X-Request-ID');
        } elseif (defined('REQUEST_ID')) {
            $extra['request_id'] = REQUEST_ID;
        } elseif (app()->bound('request.id')) {
            $extra['request_id'] = app('request.id');
        }

        // Add trace ID (can be the same as request ID or different for distributed tracing)
        if (request()->hasHeader('X-Trace-ID')) {
            $extra['trace_id'] = request()->header('X-Trace-ID');
        } elseif (isset($extra['request_id'])) {
            $extra['trace_id'] = $extra['request_id'];
        }

        // Add user ID if authenticated
        if (Auth::check()) {
            $extra['user_id'] = Auth::id();
        }

        // Add session ID
        if (session()->isStarted()) {
            $extra['session_id'] = session()->getId();
        }

        return $record->with(extra: $extra);
    }
}
