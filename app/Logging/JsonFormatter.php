<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Monolog\LogRecord;

class JsonFormatter extends MonologJsonFormatter
{
    /**
     * Format the log record.
     *
     * @param  LogRecord  $record
     * @return string
     */
    public function format(LogRecord $record): string
    {
        // Normalize the record
        $normalized = $this->normalize($record);

        // Build the structured log format
        $structuredLog = [
            'timestamp' => $normalized['datetime'],
            'level' => $normalized['level_name'],
            'message' => $normalized['message'],
            'context' => $normalized['context'] ?? [],
            'extra' => $normalized['extra'] ?? [],
        ];

        // Add service metadata
        $structuredLog['service'] = [
            'name' => config('app.name', 'marketplace'),
            'environment' => config('app.env', 'production'),
            'version' => config('app.version', '1.0.0'),
        ];

        // Add telemetry context if available
        if (isset($normalized['extra']['request_id'])) {
            $structuredLog['telemetry']['request_id'] = $normalized['extra']['request_id'];
        }

        if (isset($normalized['extra']['trace_id'])) {
            $structuredLog['telemetry']['trace_id'] = $normalized['extra']['trace_id'];
        }

        if (isset($normalized['extra']['user_id'])) {
            $structuredLog['telemetry']['user_id'] = $normalized['extra']['user_id'];
        }

        if (isset($normalized['extra']['session_id'])) {
            $structuredLog['telemetry']['session_id'] = $normalized['extra']['session_id'];
        }

        // Remove telemetry from extra to avoid duplication
        unset(
            $normalized['extra']['request_id'],
            $normalized['extra']['trace_id'],
            $normalized['extra']['user_id'],
            $normalized['extra']['session_id']
        );

        $structuredLog['extra'] = $normalized['extra'];

        return $this->toJson($structuredLog).($this->appendNewline ? "\n" : '');
    }
}
