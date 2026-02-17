# Unified Logging Framework

## Overview

This application implements a unified logging framework with structured JSON logging, request ID tracking, and telemetry context. This enables better observability, debugging, and log aggregation across the entire system.

## Features

### 1. Structured JSON Logging

All logs are output in a structured JSON format that includes:
- Timestamp
- Log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- Message
- Context data
- Service metadata (name, environment, version)
- Telemetry data (request ID, user ID, session ID, trace ID)

### 2. Request ID Tracking

Every HTTP request is assigned a unique request ID that:
- Propagates throughout the entire request lifecycle
- Is included in all log entries
- Is returned in the response headers (`X-Request-ID`)
- Can be passed from frontend to backend for correlation

### 3. Telemetry Context

Automatic enrichment of logs with:
- **Request ID**: Unique identifier for each request
- **Trace ID**: For distributed tracing across services
- **User ID**: When a user is authenticated
- **Session ID**: When a session is active

### 4. Environment Support

Different logging configurations for:
- **Local**: Detailed logs to files
- **Staging**: JSON logs with daily rotation
- **Production**: Structured JSON logs optimized for log aggregation

## Configuration

### Environment Variables

Add these to your `.env` file:

```bash
# Logging Configuration
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_DAILY_DAYS=14

# Enable JSON logging for production
# LOG_CHANNEL=json_daily

# Telemetry Configuration
TELEMETRY_ENABLED=true
TELEMETRY_INCLUDE_USER=true
TELEMETRY_INCLUDE_SESSION=true
TELEMETRY_SAMPLING_RATE=100

# For distributed tracing
TELEMETRY_REQUEST_ID_HEADER=X-Request-ID
TELEMETRY_TRACE_ID_HEADER=X-Trace-ID

# Application Metadata
APP_VERSION=1.0.0
```

### Log Channels

Available log channels:

- `single` - Single file logging (default for local)
- `daily` - Daily rotated log files
- `json` - Structured JSON logging to a single file
- `json_daily` - Structured JSON logging with daily rotation (recommended for production)
- `stack` - Multiple channels combined
- `slack` - Slack notifications for critical errors
- `stderr` - Output to standard error
- `null` - Discard logs

### Switching to JSON Logging

For production environments, update `.env`:

```bash
LOG_CHANNEL=json_daily
```

Or use a stack with both formats:

```bash
LOG_CHANNEL=stack
LOG_STACK=daily,json_daily
```

## Usage

### Basic Logging

Use Laravel's standard logging facade:

```php
use Illuminate\Support\Facades\Log;

// Different log levels
Log::debug('Debug information', ['user_id' => 123]);
Log::info('User logged in', ['user' => $user->email]);
Log::warning('High memory usage', ['memory' => memory_get_usage()]);
Log::error('Payment failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
Log::critical('Database connection lost');
```

### Helper Functions

Use the provided helper functions for cleaner code:

```php
// Log with automatic telemetry context
log_info('Order created successfully', ['order_id' => $order->id]);
log_error('Failed to process payment', ['error' => $e->getMessage()]);
log_warning('API rate limit approaching');
log_debug('Cache hit', ['key' => $cacheKey]);
log_critical('System is shutting down');

// Get the current request ID
$requestId = get_request_id();
```

### Request ID Access

Access the request ID in multiple ways:

```php
// Via helper function
$requestId = get_request_id();

// Via app container
$requestId = app('request.id');

// Via request attribute
$requestId = request()->attributes->get('request_id');

// Via constant (if defined)
$requestId = defined('REQUEST_ID') ? REQUEST_ID : null;
```

### Queue Jobs

Telemetry context is automatically preserved in queued jobs:

```php
// The request ID from the current request will be available in the job
dispatch(new ProcessOrderJob($order));

// In your job, the request ID is automatically available
class ProcessOrderJob implements ShouldQueue
{
    public function handle()
    {
        // Request ID from the original request is available
        log_info('Processing order', ['order_id' => $this->order->id]);
    }
}
```

### Console Commands

Request IDs are automatically generated for console commands:

```php
class ImportProductsCommand extends Command
{
    public function handle()
    {
        // Each command execution gets a unique request ID
        log_info('Starting product import');

        // All logs will include the same request ID
        foreach ($products as $product) {
            log_debug('Importing product', ['sku' => $product->sku]);
        }
    }
}
```

### Frontend Integration

Pass the request ID from frontend to backend:

```javascript
// In your frontend code
const requestId = generateUUID(); // or get from previous response

fetch('/api/orders', {
    headers: {
        'X-Request-ID': requestId,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(orderData)
});

// The backend will use this request ID for all logs
```

### Distributed Tracing

For microservices, pass trace IDs between services:

```php
use Illuminate\Support\Facades\Http;

// Pass trace ID to downstream services
$traceId = request()->header('X-Trace-ID') ?: get_request_id();

$response = Http::withHeaders([
    'X-Request-ID' => get_request_id(),
    'X-Trace-ID' => $traceId,
])->get('https://api.example.com/data');

// All services in the chain will have correlated logs
```

## Log Format

### JSON Log Entry Example

```json
{
    "timestamp": "2024-02-17T19:43:15.123456Z",
    "level": "INFO",
    "message": "Order created successfully",
    "context": {
        "order_id": 12345,
        "customer_id": 789
    },
    "service": {
        "name": "marketplace",
        "environment": "production",
        "version": "1.0.0"
    },
    "telemetry": {
        "request_id": "9f8a7b6c-5d4e-3f2a-1b0c-9d8e7f6a5b4c",
        "trace_id": "9f8a7b6c-5d4e-3f2a-1b0c-9d8e7f6a5b4c",
        "user_id": 789,
        "session_id": "abc123def456"
    },
    "extra": {
        "file": "/var/www/app/Http/Controllers/OrderController.php",
        "line": 123
    }
}
```

## Log Aggregation

The structured JSON format is optimized for log aggregation services:

- **Elasticsearch/ELK Stack**: Direct ingestion via Filebeat or Logstash
- **Splunk**: Parse JSON logs automatically
- **CloudWatch**: Use structured log groups
- **Datadog**: Automatic parsing of JSON logs
- **New Relic**: Structured logging integration

## Best Practices

1. **Use Appropriate Log Levels**
   - DEBUG: Detailed diagnostic information
   - INFO: General informational messages
   - WARNING: Warning messages for potentially harmful situations
   - ERROR: Error events that might still allow the application to continue
   - CRITICAL: Critical conditions that require immediate attention

2. **Include Context**
   - Always include relevant IDs (order_id, user_id, etc.)
   - Add error messages and stack traces for exceptions
   - Include timing information for performance tracking

3. **Avoid Sensitive Data**
   - Never log passwords, API keys, or tokens
   - Mask credit card numbers and PII
   - Use sanitized data in production logs

4. **Use Structured Context**
   ```php
   // Good
   log_info('Payment processed', [
       'order_id' => $order->id,
       'amount' => $order->total,
       'currency' => $order->currency
   ]);

   // Avoid
   log_info('Payment processed for order ' . $order->id . ' amount ' . $order->total);
   ```

5. **Maintain Consistency**
   - Use consistent field names across the application
   - Follow naming conventions (snake_case for keys)
   - Keep log messages clear and searchable

## Monitoring and Alerting

Set up alerts based on:
- Error rate increases
- Critical log entries
- Request ID patterns for debugging
- Response time anomalies

## Performance Considerations

- Logging is asynchronous where possible
- JSON formatting has minimal overhead
- Log rotation prevents disk space issues
- Sampling rate can be adjusted for high-traffic scenarios

## Troubleshooting

### Request ID Not Appearing in Logs

1. Ensure `LoggingServiceProvider` is registered in `bootstrap/providers.php`
2. Check that `RequestIdMiddleware` is in the middleware stack
3. Verify telemetry is enabled in `config/telemetry.php`

### JSON Logs Not Being Generated

1. Check `LOG_CHANNEL` in `.env`
2. Ensure storage/logs directory is writable
3. Verify `JsonFormatter` class is being used

### Queue Jobs Missing Context

1. Ensure `QueueTelemetryProcessor::register()` is called in a service provider
2. Check that job payload includes telemetry data

## Additional Resources

- [Laravel Logging Documentation](https://laravel.com/docs/logging)
- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [Structured Logging Best Practices](https://www.honeycomb.io/blog/structured-logging-best-practices)
