<?php

declare(strict_types=1);

use Monolog\Level;
use Monolog\LogRecord;
use StageRightLabs\CommandLineLogger\Handler;
use StageRightLabs\CommandLineLogger\Tests\RecordingOutput;
use StageRightLabs\CommandLineLogger\Tests\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

uses(TestCase::class)->in(__DIR__);

/**
 * Build a log record for testing.
 *
 * @param  array<string, mixed>  $context
 * @param  array<string, mixed>  $extra
 */
function record(
    Level $level = Level::Warning,
    string $message = 'A frog leaps in',
    array $context = [],
    array $extra = [],
): LogRecord {
    return new LogRecord(
        datetime: new DateTimeImmutable(),
        channel: 'testing',
        level: $level,
        message: $message,
        context: $context,
        extra: $extra,
    );
}

/**
 * Build a handler wired up to a recording output and an in-memory var dumper.
 *
 * The dump stream is passed back by reference so tests can read from it.
 *
 * @param  resource|null  $dumpStream
 */
function handler(RecordingOutput $output, &$dumpStream = null): Handler
{
    $dumpStream = fopen('php://memory', 'r+');

    app()->instance('command.log.output', $output);

    return new Handler(app(), new CliDumper($dumpStream), new VarCloner());
}

/**
 * Read everything written to a var dumper stream.
 *
 * @param  resource  $dumpStream
 */
function dumped($dumpStream): string
{
    rewind($dumpStream);

    return (string) stream_get_contents($dumpStream);
}

/**
 * Run the haiku fixture command through the real console kernel and return
 * everything it wrote to the console.
 *
 * The kernel only skips rerouting Symfony's command events to their Laravel
 * counterparts because we are running under a test; turning that back on
 * gives us the same CommandStarting/CommandFinished flow as a live
 * `php artisan` invocation. It has to happen before anything resolves the
 * Artisan application, which is where the dispatcher gets attached.
 *
 * @param  array<string, mixed>  $parameters
 */
function runHaikuCommand(array $parameters = []): string
{
    config()->set('logging.default', 'console');

    /** @var \Illuminate\Foundation\Console\Kernel $kernel */
    $kernel = app(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->rerouteSymfonyCommandEvents();
    $kernel->registerCommand(new \StageRightLabs\CommandLineLogger\Tests\Fixtures\HaikuCommand());

    $output = new RecordingOutput();

    $kernel->handle(
        new \Symfony\Component\Console\Input\ArrayInput(['command' => 'haiku'] + $parameters),
        $output
    );

    return $output->contents();
}
