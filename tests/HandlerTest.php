<?php

declare(strict_types=1);

use Monolog\Level;
use StageRightLabs\CommandLineLogger\Handler;
use StageRightLabs\CommandLineLogger\Tests\RecordingOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

it('handles records according to the output verbosity', function (int $verbosity, Level $level, bool $expected) {
    $output = new RecordingOutput($verbosity, decorated: false);

    expect(handler($output)->isHandling(record($level)))->toBe($expected);
})->with([
    // Silent: nothing at all
    'silent, emergency' => [OutputInterface::VERBOSITY_SILENT, Level::Emergency, false],
    'silent, error' => [OutputInterface::VERBOSITY_SILENT, Level::Error, false],

    // Quiet: errors and above
    'quiet, error' => [OutputInterface::VERBOSITY_QUIET, Level::Error, true],
    'quiet, warning' => [OutputInterface::VERBOSITY_QUIET, Level::Warning, false],

    // Normal: warnings and above
    'normal, warning' => [OutputInterface::VERBOSITY_NORMAL, Level::Warning, true],
    'normal, notice' => [OutputInterface::VERBOSITY_NORMAL, Level::Notice, false],

    // Verbose: notices and above
    'verbose, notice' => [OutputInterface::VERBOSITY_VERBOSE, Level::Notice, true],
    'verbose, info' => [OutputInterface::VERBOSITY_VERBOSE, Level::Info, false],

    // Very verbose: info and above
    'very verbose, info' => [OutputInterface::VERBOSITY_VERY_VERBOSE, Level::Info, true],
    'very verbose, debug' => [OutputInterface::VERBOSITY_VERY_VERBOSE, Level::Debug, false],

    // Debug: everything
    'debug, debug' => [OutputInterface::VERBOSITY_DEBUG, Level::Debug, true],
    'debug, emergency' => [OutputInterface::VERBOSITY_DEBUG, Level::Emergency, true],
]);

it('writes handled messages to the console', function () {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_NORMAL, decorated: false);

    handler($output)->handle(record(Level::Warning, 'A frog leaps in'));

    expect($output->contents())->toBe('[WARNING] A frog leaps in' . PHP_EOL);
});

it('does not write messages that fall below the verbosity threshold', function () {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_NORMAL, decorated: false);

    handler($output)->handle(record(Level::Info, 'A frog leaps in'));

    expect($output->contents())->toBe('');
});

it('allows records to bubble to other handlers', function () {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_DEBUG, decorated: false);

    expect(handler($output)->handle(record(Level::Error)))->toBeFalse();
});

it('writes a decorated prefix when the output supports it', function (Level $level, string $label) {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_DEBUG, decorated: true);

    handler($output)->handle(record($level, 'To an old pond'));

    expect($output->contents())
        ->toContain($label)
        ->toContain('To an old pond')
        ->toContain("\033["); // ANSI escape sequence
})->with([
    'debug' => [Level::Debug, 'DEBUG'],
    'info' => [Level::Info, 'INFO'],
    'notice' => [Level::Notice, 'NOTICE'],
    'warning' => [Level::Warning, 'WARNING'],
    'error' => [Level::Error, 'ERROR'],
    'critical' => [Level::Critical, 'CRITICAL'],
    'alert' => [Level::Alert, 'ALERT'],
    'emergency' => [Level::Emergency, 'EMERGENCY'],
]);

it('dumps the context and extra payloads', function () {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_DEBUG, decorated: false);
    $handler = handler($output, $dumpStream);

    $handler->handle(record(
        level: Level::Error,
        context: ['haiku' => 'Matsu Basho'],
        extra: ['sound' => 'of the water'],
    ));

    expect(dumped($dumpStream))
        ->toContain('sound')
        ->toContain('of the water')
        ->toContain('haiku')
        ->toContain('Matsu Basho');
});

it('does not dump anything when there is no context or extra payload', function () {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_DEBUG, decorated: false);
    $handler = handler($output, $dumpStream);

    $handler->handle(record(Level::Error));

    expect(dumped($dumpStream))->toBe('');
});

it('is inert when there is no command running', function () {
    // Nothing is bound at all: a web request, a queue worker, anything that
    // reaches the console channel through a log stack outside of a command.
    expect(app()->bound('command.log.output'))->toBeFalse();

    $handler = new Handler(app(), new CliDumper, new VarCloner);

    expect(fn () => $handler->handle(record(Level::Emergency)))->not->toThrow(Throwable::class)
        ->and($handler->handle(record(Level::Emergency)))->toBeFalse()
        ->and($handler->isHandling(record(Level::Emergency)))->toBeFalse();
});

it('is inert once the command output has been released', function () {
    // This is what Subscriber::release() leaves behind after a command ends.
    app()->bind('command.log.output', fn () => null);

    $handler = new Handler(app(), new CliDumper, new VarCloner);

    expect($handler->handle(record(Level::Emergency)))->toBeFalse()
        ->and($handler->isHandling(record(Level::Emergency)))->toBeFalse();
});

it('accepts any output implementation, not just ConsoleOutput', function () {
    // Artisan::call() hands the CommandStarting event a BufferedOutput.
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, decorated: false);

    app()->instance('command.log.output', $output);

    $handler = new Handler(app(), new CliDumper, new VarCloner);
    $handler->handle(record(Level::Warning, 'A frog leaps in'));

    expect($output->fetch())->toBe('[WARNING] A frog leaps in' . PHP_EOL);
});

it('picks up the output of whichever command is currently running', function () {
    // The handler is resolved once by the log manager and then reused, so it
    // must not cache the output of the first command it happens to see.
    $handler = new Handler(app(), new CliDumper, new VarCloner);

    $first = new RecordingOutput(OutputInterface::VERBOSITY_NORMAL, decorated: false);
    app()->instance('command.log.output', $first);
    $handler->handle(record(Level::Warning, 'To an old pond'));

    $second = new RecordingOutput(OutputInterface::VERBOSITY_NORMAL, decorated: false);
    app()->instance('command.log.output', $second);
    $handler->handle(record(Level::Warning, 'A frog leaps in'));

    expect($first->contents())->toBe('[WARNING] To an old pond' . PHP_EOL)
        ->and($second->contents())->toBe('[WARNING] A frog leaps in' . PHP_EOL);
});

it('handles a batch of records', function () {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_NORMAL, decorated: false);

    handler($output)->handleBatch([
        record(Level::Error, 'To an old pond'),
        record(Level::Info, 'A frog leaps in'),
        record(Level::Warning, 'And the sound of the water'),
    ]);

    expect($output->contents())
        ->toContain('[ERROR] To an old pond')
        ->not->toContain('A frog leaps in')
        ->toContain('[WARNING] And the sound of the water');
});
