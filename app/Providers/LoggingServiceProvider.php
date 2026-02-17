<?php

namespace App\Providers;

use App\Logging\JsonFormatter;
use App\Logging\TelemetryProcessor;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register telemetry processor
        $this->app->singleton(TelemetryProcessor::class);

        // Register JSON formatter
        $this->app->singleton(JsonFormatter::class, function ($app) {
            return new JsonFormatter();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure custom log channels
        $this->configureCustomChannels();

        // Add telemetry processor to existing channels
        $this->addTelemetryToChannels();
    }

    /**
     * Configure custom log channels.
     *
     * @return void
     */
    protected function configureCustomChannels(): void
    {
        /** @var LogManager $logManager */
        $logManager = $this->app->make('log');

        // Configure JSON structured logging channel
        $logManager->extend('json', function ($app, array $config) {
            $handler = new StreamHandler(
                $config['path'] ?? storage_path('logs/laravel.log'),
                $config['level'] ?? Logger::DEBUG
            );

            $handler->setFormatter($app->make(JsonFormatter::class));

            $logger = new Logger($config['name'] ?? 'json');
            $logger->pushHandler($handler);
            $logger->pushProcessor($app->make(TelemetryProcessor::class));

            return $logger;
        });

        // Configure JSON daily logging channel
        $logManager->extend('json_daily', function ($app, array $config) {
            $handler = new \Monolog\Handler\RotatingFileHandler(
                $config['path'] ?? storage_path('logs/laravel.log'),
                $config['days'] ?? 14,
                $config['level'] ?? Logger::DEBUG
            );

            $handler->setFormatter($app->make(JsonFormatter::class));

            $logger = new Logger($config['name'] ?? 'json_daily');
            $logger->pushHandler($handler);
            $logger->pushProcessor($app->make(TelemetryProcessor::class));

            return $logger;
        });
    }

    /**
     * Add telemetry processor to existing log channels.
     *
     * @return void
     */
    protected function addTelemetryToChannels(): void
    {
        $channels = config('logging.telemetry_enabled_channels', ['single', 'daily', 'stack']);

        foreach ($channels as $channel) {
            try {
                $logger = $this->app->make('log')->channel($channel);

                if (method_exists($logger, 'getLogger')) {
                    $monolog = $logger->getLogger();

                    if ($monolog instanceof Logger) {
                        $monolog->pushProcessor($this->app->make(TelemetryProcessor::class));
                    }
                }
            } catch (\Exception $e) {
                // Channel doesn't exist or can't be configured, skip it
                continue;
            }
        }
    }
}
