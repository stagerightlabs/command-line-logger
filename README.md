![Write Laravel log messages to the console](https://banners.beyondco.de/Command%20Line%20Logger.png?theme=light&packageManager=composer+require&packageName=stagerightlabs%2Fcommand-line-logger&pattern=graphPaper&style=style_1&description=Write+log+messages+to+the+console+while+respecting+output+verbosity&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)

# Write log messages to the command line while respecting output verbosity

Artisan commands accepts 'verbosity' level flags which indicate the level of desired output: `-v`, `-vv` and `-vvv`. These come from the underlying Symfony/Console package and are often overlooked in the Laravel ecosystem. Respecting verbosity can be rather cumbersome and adds noise to your code:

```php
if ($this->getOutput()->isDebug()) {
    $this->info('To an old pond');
}

if ($this->getOutput()->isVeryVerbose()) {
    $this->info('A frog leaps in');
}

if ($this->getOutput()->isVerbose()) {
    $this->info('And the sound of the water');
}

$this->info('- Matsu Basho');
```

The goal of this package is to streamline command output while still respecting verbosity flags. We do that by creating a `console` log channel and sending log messages to the command line rather than using the native Artisan output helpers such as `info()` and `error()`.

With this package installed and configured, the above example would instead look like this:

```php
Log::debug('To an old pond');
Log::info('A frog leaps in');
Log::notice('And the sound of the water');
Log::warning('- Matsu Basho');
```

When you call the command those messages would appear in the console output based on the verbosity flag provided:

| Level   | Verbosity    |
| ------- | ------------ |
| Error   | Quiet        |
| Warning | Normal       |
| Notice  | Verbose      |
| Info    | Very Verbose |
| Debug   | Debug        |

When combined with a log stack the output will also be logged to those other channels as well, which is an added bonus.

This package is an implementation of the Symfony Monolog Bridge [Console Handler](https://symfony.com/doc/current/logging/monolog_console.html) for Laravel.

## Installation

You can install the package via composer:

```bash
composer require stagerightlabs/command-line-logger
```

Make sure you do not have an existing log channel called "console" otherwise there may be conflicts.

After installing the package you will need to add the "console" destination channel to your logging config. This can be done in a `.env` file:

```
LOG_CHANNEL=stack
LOG_STACK=single,console
```

You could also make this change in the `config/logging.php` file:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'console'],
    'ignore_exceptions' => false,
],
```

You don't need to use the "console" channel with a stack but it can be helpful.

## Usage

Instead of writing console output directly from the Artisan command, you should now write to the logs instead:

```php
// Instead of this
$this->info('Matsu Basho');

// Do this
Log::info('Matsu Basho');
```

These messages will appear in the console depending on the log level and the verbosity settings given to the command. See the above table for more details.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email ryan@stagerightlabs.com instead of using the issue tracker.

## Credits

The original idea comes from the [Symfony Monolog Bridge](https://github.com/symfony/symfony/blob/727ae99526ed907e5abc5e8ee59187c2139b1096/src/Symfony/Bridge/Monolog/Handler/ConsoleHandler.php). More information in [the Symfony Docs](https://symfony.com/doc/current/logging/monolog_console.html).

For this version:

-   [Ryan C. Durham](https://github.com/stagerightlabs)
-   [All Contributors](../../contributors)

## License

The Apache License 2. Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
