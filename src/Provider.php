<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Add a 'console' config entry for the command line logger
        Config::set('logging.channels.console', [
            'driver' => 'console',
            'handler' => Handler::class,
        ]);

        // Register a 'console' Monolog driver
        $this->callAfterResolving(LogManager::class, function (LogManager $log) {
            $log->extend('console', function () {
                return $this->createMonologDriver([
                    'handler' => Handler::class,
                ]);
            });
        });
    }

    /**
     * Register the console event subscriber.
     */
    public function boot(): void
    {
        Event::subscribe(Subscriber::class);
    }
}
