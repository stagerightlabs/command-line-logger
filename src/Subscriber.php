<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Container\Container;

class Subscriber
{
    /**
     * @codeCoverageIgnore
     */
    public function __construct(private Container $container) {}

    /**
     * Capture command output when a command starts.
     */
    public function capture(CommandStarting $event): void
    {
        $this->container->bind('command.log.output', function () use ($event) {
            return $event->output;
        });
    }

    /**
     * Release command output when a command finishes.
     */
    public function release(CommandFinished $event): void
    {
        $this->container->bind('command.log.output', function () {
            return null;
        });
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            CommandStarting::class => 'capture',
            CommandFinished::class => 'release',
        ];
    }
}
