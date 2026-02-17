# Unified Logging Framework - Quick Start Guide

## 🚀 Getting Started in 5 Minutes

### Step 1: Configure Your Environment

Copy the logging configuration to your `.env` file:

```bash
# For local development
LOG_CHANNEL=stack
LOG_LEVEL=debug
TELEMETRY_ENABLED=true

# For production (recommended)
# LOG_CHANNEL=json_daily
# LOG_LEVEL=info
```

### Step 2: Start Logging

Use Laravel's Log facade or helper functions:

```php
// Using Log facade
use Illuminate\Support\Facades\Log;
Log::info('Order created', ['order_id' => 123]);

// Using helper functions (recommended)
log_info('Order created', ['order_id' => 123]);
log_error('Payment failed', ['error' => $e->getMessage()]);
```

### Step 3: View Your Logs

JSON logs are written to `storage/logs/laravel-json.log`:

```bash
# View latest JSON logs
tail -f storage/logs/laravel-json.log | jq

# Search by request ID
cat storage/logs/laravel-json.log | jq 'select(.telemetry.request_id == "your-request-id")'

# View only errors
cat storage/logs/laravel-json.log | jq 'select(.level == "ERROR")'
```

## 📋 Common Use Cases

### Log in a Controller

```php
public function store(Request $request)
{
    log_info('Creating order', ['customer_id' => $request->user()->id]);

    try {
        $order = Order::create($request->validated());
        log_info('Order created', ['order_id' => $order->id]);
        return response()->json($order);
    } catch (\Exception $e) {
        log_error('Order creation failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed'], 500);
    }
}
```

### Log in a Service

```php
public function processPayment($order)
{
    log_info('Processing payment', ['order_id' => $order->id]);
    // ... payment logic
}
```

### Log in a Queue Job

```php
public function handle()
{
    // Request ID is automatically preserved from the original request
    log_info('Processing job', ['job_id' => $this->job->getJobId()]);
    // ... job logic
}
```

### Log in a Console Command

```php
public function handle()
{
    // Unique request ID is automatically generated
    log_info('Command started', ['command' => $this->signature]);
    // ... command logic
}
```

## 🔍 Debugging with Request IDs

Every request gets a unique ID that's included in all logs:

```php
// Get the current request ID
$requestId = get_request_id();

// Pass it to the frontend
return response()->json([
    'data' => $data,
    'request_id' => $requestId
]);
```

### Track a Request Across Systems

1. Frontend sends request with ID:
```javascript
fetch('/api/orders', {
    headers: { 'X-Request-ID': requestId }
})
```

2. Backend automatically uses this ID in all logs

3. Search logs by request ID to see the full request flow:
```bash
cat storage/logs/laravel-json.log | jq 'select(.telemetry.request_id == "abc-123")'
```

## 📊 Log Levels Guide

| Level | When to Use | Example |
|-------|-------------|---------|
| `debug` | Development details | `log_debug('Cache miss', ['key' => $key])` |
| `info` | Normal operations | `log_info('User logged in', ['user_id' => 123])` |
| `warning` | Unusual but handled | `log_warning('API slow response', ['time' => 2.5])` |
| `error` | Errors that don't crash | `log_error('Payment failed', ['order_id' => 123])` |
| `critical` | Critical failures | `log_critical('Database down')` |

## 🎯 Best Practices

### ✅ DO

```php
// Include relevant context
log_info('Order created', [
    'order_id' => $order->id,
    'total' => $order->total,
    'customer_id' => $order->customer_id
]);

// Log both success and failure
try {
    $result = $this->processPayment();
    log_info('Payment successful', ['transaction_id' => $result->id]);
} catch (\Exception $e) {
    log_error('Payment failed', ['error' => $e->getMessage()]);
}
```

### ❌ DON'T

```php
// Don't log sensitive data
log_info('User login', ['password' => $password]); // NEVER!

// Don't concatenate strings
log_info('Order ' . $id . ' created'); // Hard to parse

// Don't log without context
log_info('Something happened'); // Not useful
```

## 🔧 Production Configuration

For production, use these settings in `.env`:

```bash
LOG_CHANNEL=json_daily
LOG_LEVEL=info
LOG_DAILY_DAYS=30
TELEMETRY_ENABLED=true
TELEMETRY_SAMPLING_RATE=100
APP_VERSION=1.0.0
```

## 📚 More Information

- See [LOGGING.md](LOGGING.md) for complete documentation
- See [LOGGING_EXAMPLES.md](LOGGING_EXAMPLES.md) for more examples
- See [.env.logging.example](.env.logging.example) for all configuration options

## 🆘 Troubleshooting

### Logs not showing telemetry data?
- Check `TELEMETRY_ENABLED=true` in `.env`
- Verify `LoggingServiceProvider` is registered
- Clear config cache: `php artisan config:clear`

### Request ID not in logs?
- Verify `RequestIdMiddleware` is registered
- Check middleware stack with `php artisan route:list`

### Can't find logs?
- Default location: `storage/logs/laravel.log`
- JSON logs: `storage/logs/laravel-json.log`
- Ensure storage directory is writable

## 💡 Quick Tips

1. **Use helper functions** - Cleaner than Log facade
2. **Always include context** - Makes logs searchable
3. **Use appropriate levels** - Don't log everything as ERROR
4. **Track request IDs** - Essential for debugging
5. **Check logs regularly** - Don't wait for problems

---

**Need help?** Check the full documentation in [LOGGING.md](LOGGING.md)
