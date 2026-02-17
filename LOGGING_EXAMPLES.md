# Unified Logging Framework - Usage Examples

## Quick Start Examples

### Example 1: Basic Logging in a Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Log the start of order creation
        Log::info('Creating new order', [
            'customer_id' => $request->user()->id,
            'items_count' => count($request->items)
        ]);

        try {
            $order = $this->orderService->create($request->validated());

            // Log success
            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'total' => $order->total
            ]);

            return response()->json(['order' => $order], 201);

        } catch (\Exception $e) {
            // Log error with exception details
            Log::error('Failed to create order', [
                'customer_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Order creation failed'], 500);
        }
    }
}
```

**Sample JSON Log Output:**
```json
{
    "timestamp": "2024-02-17T19:43:15.123456Z",
    "level": "INFO",
    "message": "Order created successfully",
    "context": {
        "order_id": 12345,
        "total": 299.99
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
    }
}
```

### Example 2: Using Helper Functions

```php
<?php

namespace App\Services;

class PaymentService
{
    public function processPayment($order, $paymentMethod)
    {
        // Using helper functions for cleaner code
        log_info('Processing payment', [
            'order_id' => $order->id,
            'amount' => $order->total,
            'method' => $paymentMethod
        ]);

        try {
            $result = $this->paymentGateway->charge(
                $order->total,
                $paymentMethod
            );

            log_info('Payment processed successfully', [
                'order_id' => $order->id,
                'transaction_id' => $result->transaction_id
            ]);

            return $result;

        } catch (PaymentException $e) {
            log_error('Payment processing failed', [
                'order_id' => $order->id,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
```

### Example 3: Queue Job with Context Preservation

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $orderId
    ) {}

    public function handle()
    {
        // Request ID from the original request is automatically available
        log_info('Starting order processing', [
            'order_id' => $this->orderId,
            'queue' => $this->queue
        ]);

        $order = Order::find($this->orderId);

        // Process order items
        foreach ($order->items as $item) {
            log_debug('Processing order item', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'sku' => $item->sku
            ]);

            $this->processItem($item);
        }

        log_info('Order processing completed', [
            'order_id' => $this->orderId,
            'items_processed' => $order->items->count()
        ]);
    }

    public function failed(\Throwable $exception)
    {
        log_critical('Order processing job failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
```

### Example 4: Console Command with Logging

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportProductsCommand extends Command
{
    protected $signature = 'products:import {file}';
    protected $description = 'Import products from CSV file';

    public function handle()
    {
        // Command automatically gets a unique request ID
        $file = $this->argument('file');

        log_info('Starting product import', [
            'file' => $file,
            'command' => $this->signature
        ]);

        try {
            $products = $this->parseFile($file);

            $imported = 0;
            $failed = 0;

            foreach ($products as $productData) {
                try {
                    $this->importProduct($productData);
                    $imported++;

                    if ($imported % 100 === 0) {
                        log_info('Import progress', [
                            'imported' => $imported,
                            'failed' => $failed
                        ]);
                    }
                } catch (\Exception $e) {
                    $failed++;
                    log_error('Failed to import product', [
                        'sku' => $productData['sku'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            log_info('Product import completed', [
                'total' => count($products),
                'imported' => $imported,
                'failed' => $failed
            ]);

            $this->info("Import completed: {$imported} imported, {$failed} failed");

        } catch (\Exception $e) {
            log_critical('Product import failed', [
                'file' => $file,
                'error' => $e->getMessage()
            ]);

            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
```

### Example 5: Middleware with Request Tracking

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiRateLimitMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'api_rate_limit:' . $request->user()->id;

        log_debug('Checking API rate limit', [
            'user_id' => $request->user()->id,
            'endpoint' => $request->path()
        ]);

        if ($this->isRateLimited($key)) {
            log_warning('API rate limit exceeded', [
                'user_id' => $request->user()->id,
                'endpoint' => $request->path(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'request_id' => get_request_id()
            ], 429);
        }

        $this->incrementRateLimit($key);

        return $next($request);
    }
}
```

### Example 6: Event Listener with Context

```php
<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use Illuminate\Support\Facades\Mail;

class SendShipmentNotification
{
    public function handle(OrderShipped $event)
    {
        log_info('Processing order shipped event', [
            'order_id' => $event->order->id,
            'tracking_number' => $event->shipment->tracking_number
        ]);

        try {
            Mail::to($event->order->customer)
                ->send(new ShipmentNotification($event->order, $event->shipment));

            log_info('Shipment notification sent', [
                'order_id' => $event->order->id,
                'customer_email' => $event->order->customer->email
            ]);

        } catch (\Exception $e) {
            log_error('Failed to send shipment notification', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### Example 7: API Client with Distributed Tracing

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class InventoryServiceClient
{
    public function checkStock($sku, $quantity)
    {
        // Get current request/trace IDs for correlation
        $requestId = get_request_id();
        $traceId = request()->header('X-Trace-ID', $requestId);

        log_info('Checking inventory stock', [
            'sku' => $sku,
            'quantity' => $quantity,
            'trace_id' => $traceId
        ]);

        try {
            // Pass IDs to downstream service
            $response = Http::withHeaders([
                'X-Request-ID' => $requestId,
                'X-Trace-ID' => $traceId,
            ])->get(config('services.inventory.url') . '/stock', [
                'sku' => $sku,
                'quantity' => $quantity
            ]);

            if ($response->successful()) {
                $data = $response->json();

                log_info('Inventory stock retrieved', [
                    'sku' => $sku,
                    'available' => $data['available'],
                    'trace_id' => $traceId
                ]);

                return $data;
            }

            log_error('Inventory service returned error', [
                'sku' => $sku,
                'status' => $response->status(),
                'trace_id' => $traceId
            ]);

            throw new \Exception('Inventory check failed');

        } catch (\Exception $e) {
            log_error('Failed to check inventory', [
                'sku' => $sku,
                'error' => $e->getMessage(),
                'trace_id' => $traceId
            ]);

            throw $e;
        }
    }
}
```

### Example 8: Model Observer with Logging

```php
<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    public function created(Order $order)
    {
        log_info('Order created', [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'total' => $order->total,
            'status' => $order->status
        ]);
    }

    public function updated(Order $order)
    {
        $changes = $order->getChanges();

        if (isset($changes['status'])) {
            log_info('Order status changed', [
                'order_id' => $order->id,
                'old_status' => $order->getOriginal('status'),
                'new_status' => $changes['status']
            ]);
        }
    }

    public function deleted(Order $order)
    {
        log_warning('Order deleted', [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id
        ]);
    }
}
```

## Log Searching Examples

### Search by Request ID

All logs for a specific request can be found using the request ID:

```bash
# Using grep on JSON logs
grep "9f8a7b6c-5d4e-3f2a-1b0c-9d8e7f6a5b4c" storage/logs/laravel-json.log

# Using jq for pretty output
cat storage/logs/laravel-json.log | jq 'select(.telemetry.request_id == "9f8a7b6c-5d4e-3f2a-1b0c-9d8e7f6a5b4c")'
```

### Search by User ID

```bash
# Find all logs for a specific user
cat storage/logs/laravel-json.log | jq 'select(.telemetry.user_id == 789)'
```

### Search by Log Level

```bash
# Find all errors
cat storage/logs/laravel-json.log | jq 'select(.level == "ERROR")'

# Find all critical and error logs
cat storage/logs/laravel-json.log | jq 'select(.level == "ERROR" or .level == "CRITICAL")'
```

### Search by Time Range

```bash
# Logs from the last hour
cat storage/logs/laravel-json.log | jq 'select(.timestamp > "2024-02-17T18:00:00Z")'
```

## Integration with Log Aggregation Services

### Elasticsearch Query Examples

```json
{
  "query": {
    "bool": {
      "must": [
        { "match": { "telemetry.request_id": "9f8a7b6c-5d4e-3f2a-1b0c-9d8e7f6a5b4c" }},
        { "match": { "level": "ERROR" }}
      ]
    }
  }
}
```

### Splunk Query Examples

```
index=marketplace telemetry.request_id="9f8a7b6c-5d4e-3f2a-1b0c-9d8e7f6a5b4c"
| table timestamp, level, message, context.*
```

### CloudWatch Insights Query Examples

```
fields @timestamp, level, message, telemetry.request_id, context
| filter telemetry.request_id = "9f8a7b6c-5d4e-3f2a-1b0c-9d8e7f6a5b4c"
| sort @timestamp desc
```

## Best Practices Summary

1. **Always log at appropriate levels**
2. **Include relevant context** (IDs, values, states)
3. **Don't log sensitive data** (passwords, tokens, PII)
4. **Use structured context** (arrays, not string concatenation)
5. **Log both success and failure cases**
6. **Include timing information** for performance tracking
7. **Use request IDs** for debugging and tracing
8. **Maintain consistency** in field names and formats
