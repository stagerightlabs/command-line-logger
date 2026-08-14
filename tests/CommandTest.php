<?php

declare(strict_types=1);

/**
 * End to end coverage: run a real Artisan command through the console kernel
 * and assert on what the verbosity flags actually produce. These are the
 * expectations documented in the package README.
 */

it('writes only warnings and above at normal verbosity', function () {
    expect(runHaikuCommand())
        ->not->toContain('To an old pond')        // debug
        ->not->toContain('A frog leaps in')       // info
        ->not->toContain('And the sound')         // notice
        ->toContain('[WARNING] - Matsu Basho');
});

it('writes notices and above with -v', function () {
    expect(runHaikuCommand(['-v' => true]))
        ->not->toContain('To an old pond')
        ->not->toContain('A frog leaps in')
        ->toContain('[NOTICE] And the sound of the water')
        ->toContain('[WARNING] - Matsu Basho');
});

it('writes info and above with -vv', function () {
    expect(runHaikuCommand(['-vv' => true]))
        ->not->toContain('To an old pond')
        ->toContain('[INFO] A frog leaps in')
        ->toContain('[NOTICE] And the sound of the water')
        ->toContain('[WARNING] - Matsu Basho');
});

it('writes everything with -vvv', function () {
    expect(runHaikuCommand(['-vvv' => true]))
        ->toContain('[DEBUG] To an old pond')
        ->toContain('[INFO] A frog leaps in')
        ->toContain('[NOTICE] And the sound of the water')
        ->toContain('[WARNING] - Matsu Basho');
});

it('writes nothing below the error level when quiet', function () {
    expect(runHaikuCommand(['-q' => true]))->toBe('');
});

it('releases the command output once the command has finished', function () {
    runHaikuCommand();

    expect(app()->get('command.log.output'))->toBeNull();
});
