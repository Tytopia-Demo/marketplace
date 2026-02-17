<?php

namespace App\Logging;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class ConsoleTelemetryHandler
{
    /**
     * Register console event listeners to set telemetry context.
     *
     * @return void
     */
    public static function register(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            // Generate request ID for console commands
            $requestId = Str::uuid()->toString();

            // Store request ID globally
            app()->instance('request.id', $requestId);

            if (! defined('REQUEST_ID')) {
                define('REQUEST_ID', $requestId);
            }

            // Add command context for logging
            app()->instance('command.name', $event->command);
        });
    }
}
