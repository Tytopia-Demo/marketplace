<?php

if (! function_exists('log_with_context')) {
    /**
     * Log a message with automatic telemetry context.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    function log_with_context(string $level, string $message, array $context = []): void
    {
        $logger = app('log');

        $logger->log($level, $message, $context);
    }
}

if (! function_exists('get_request_id')) {
    /**
     * Get the current request ID.
     *
     * @return string|null
     */
    function get_request_id(): ?string
    {
        if (app()->bound('request.id')) {
            return app('request.id');
        }

        if (defined('REQUEST_ID')) {
            return REQUEST_ID;
        }

        return null;
    }
}

if (! function_exists('log_debug')) {
    /**
     * Log a debug message with context.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    function log_debug(string $message, array $context = []): void
    {
        log_with_context('debug', $message, $context);
    }
}

if (! function_exists('log_info')) {
    /**
     * Log an info message with context.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    function log_info(string $message, array $context = []): void
    {
        log_with_context('info', $message, $context);
    }
}

if (! function_exists('log_warning')) {
    /**
     * Log a warning message with context.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    function log_warning(string $message, array $context = []): void
    {
        log_with_context('warning', $message, $context);
    }
}

if (! function_exists('log_error')) {
    /**
     * Log an error message with context.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    function log_error(string $message, array $context = []): void
    {
        log_with_context('error', $message, $context);
    }
}

if (! function_exists('log_critical')) {
    /**
     * Log a critical message with context.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    function log_critical(string $message, array $context = []): void
    {
        log_with_context('critical', $message, $context);
    }
}
