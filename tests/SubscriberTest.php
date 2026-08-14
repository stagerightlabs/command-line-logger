<?php

declare(strict_types=1);

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use StageRightLabs\CommandLineLogger\Subscriber;
use StageRightLabs\CommandLineLogger\Tests\RecordingOutput;
use Symfony\Component\Console\Input\ArrayInput;

it('listens for the command lifecycle events', function () {
    expect((new Subscriber(app()))->subscribe())->toBe([
        CommandStarting::class => 'capture',
        CommandFinished::class => 'release',
    ]);
});

it('captures the command output when a command starts', function () {
    $output = new RecordingOutput();

    (new Subscriber(app()))->capture(
        new CommandStarting('test:command', new ArrayInput([]), $output)
    );

    expect(app()->get('command.log.output'))->toBe($output);
});

it('releases the command output when a command finishes', function () {
    $output = new RecordingOutput();
    $subscriber = new Subscriber(app());

    $subscriber->capture(new CommandStarting('test:command', new ArrayInput([]), $output));
    $subscriber->release(new CommandFinished('test:command', new ArrayInput([]), $output, 0));

    expect(app()->get('command.log.output'))->toBeNull();
});
