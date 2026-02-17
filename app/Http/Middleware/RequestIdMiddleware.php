<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or retrieve request ID
        $requestId = $request->header('X-Request-ID') ?: $this->generateRequestId();

        // Store request ID globally for access throughout the request lifecycle
        app()->instance('request.id', $requestId);

        // Define as constant for backward compatibility
        if (! defined('REQUEST_ID')) {
            define('REQUEST_ID', $requestId);
        }

        // Add request ID to the request for easy access
        $request->attributes->set('request_id', $requestId);

        // Process the request
        $response = $next($request);

        // Add request ID to response headers for correlation
        $response->headers->set('X-Request-ID', $requestId);

        // Add trace ID if provided (for distributed tracing)
        if ($request->hasHeader('X-Trace-ID')) {
            $response->headers->set('X-Trace-ID', $request->header('X-Trace-ID'));
        }

        return $response;
    }

    /**
     * Generate a unique request ID.
     *
     * @return string
     */
    protected function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }
}
