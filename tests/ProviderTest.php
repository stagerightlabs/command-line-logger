<?php

declare(strict_types=1);

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use StageRightLabs\CommandLineLogger\Handler;
use StageRightLabs\CommandLineLogger\Tests\RecordingOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

it('registers a console logging channel', function () {
    expect(Config::get('logging.channels.console'))->toBe([
        'driver' => 'console',
        'handler' => Handler::class,
    ]);
});

it('resolves the console channel with our handler attached', function () {
    app()->instance('command.log.output', new RecordingOutput());

    $logger = Log::channel('console')->getLogger();

    expect($logger)->toBeInstanceOf(Logger::class)
        ->and($logger->getHandlers())->toHaveCount(1)
        ->and($logger->getHandlers()[0])->toBeInstanceOf(Handler::class);
});

it('subscribes to the command lifecycle events', function () {
    $output = new RecordingOutput();

    Event::dispatch(new CommandStarting('test:command', new ArrayInput([]), $output));

    expect(app()->get('command.log.output'))->toBe($output);
});

it('writes log messages to the console output', function () {
    $output = new RecordingOutput(OutputInterface::VERBOSITY_NORMAL, decorated: false);

    Event::dispatch(new CommandStarting('test:command', new ArrayInput([]), $output));

    Log::channel('console')->info('A frog leaps in');
    Log::channel('console')->warning('And the sound of the water');

    expect($output->contents())->toBe('[WARNING] And the sound of the water' . PHP_EOL);
});
