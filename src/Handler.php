<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger;

use Illuminate\Container\Container;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class Handler implements HandlerInterface
{
    /**
     *
     *
     * @var ConsoleOutput
     */
    private ConsoleOutput $output;

    /**
     * @see Symfony\Component\Console\Formatter\OutputFormatterInterface
     */
    private const VERBOSITY_SILENT = 8;
    private const VERBOSITY_QUIET = 16;
    private const VERBOSITY_NORMAL = 32;
    private const VERBOSITY_VERBOSE = 64;
    private const VERBOSITY_VERY_VERBOSE = 128;
    private const VERBOSITY_DEBUG = 256;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        private Container $container,
        private CliDumper $dumper,
        private VarCloner $cloner
    ) {
        $this->output = $this->container->get('command.log.output');
    }

    /**
     * Closes the handler. Required for the HandlerInterface but not needed.
     */
    public function close(): void {}

    /**
     * Checks whether the given record will be handled by this handler.
     */
    public function isHandling(LogRecord $record): bool
    {
        return $this->shouldBeHandled($record);
    }

    /**
     * Handle a set of records at once.
     */
    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    /**
     * Handle a record.
     */
    public function handle(LogRecord $record): bool
    {
        if ($this->shouldBeHandled($record)) {
            $this->write($record);
        }

        // Return false to allow bubbling
        return false;
    }

    /**
     * Use verbosity level to determine if a message should be logged.
     */
    protected function shouldBeHandled(LogRecord $record): bool
    {
        return match ($this->output->getVerbosity()) {
            self::VERBOSITY_SILENT => false,
            self::VERBOSITY_QUIET => $record->level->value >= 400,
            self::VERBOSITY_NORMAL => $record->level->value >= 300,
            self::VERBOSITY_VERBOSE => $record->level->value >= 250,
            self::VERBOSITY_VERY_VERBOSE => $record->level->value >= 200,
            self::VERBOSITY_DEBUG => $record->level->value >= 100,
            default => false,
        };
    }

    /**
     * Write a message to console output.
     */
    protected function write(LogRecord $record): void
    {
        // Prepare a message prefix based on log level
        $level = match ($this->output->isDecorated()) {
            true => $this->getDecoratedMessagePrefix($record->level),
            false => '[' . strtoupper($record->level->name) . '] ',
        };

        // Write the message to console output
        $this->output->writeln($level . $record->message);

        // Dump out the 'extra' payload if present
        if ($extra = $record->extra) {
            $this->dumper->dump($this->cloner->cloneVar($extra));
        }

        // Dump out the 'context' payload if present
        if ($context = $record->context) {
            $this->dumper->dump($this->cloner->cloneVar($context));
        }
    }

    /**
     * Return a decorated string representing the log level.
     */
    protected function getDecoratedMessagePrefix(Level $level): string
    {
        return match ($level) {
            Level::Debug => '<fg=white;bg=gray>  DEBUG  </> ',
            Level::Info => '<fg=white;bg=green>   INFO  </> ',
            Level::Notice => '<fg=white;bg=cyan> NOTICE  </> ',
            Level::Warning => '<fg=white;bg=yellow> WARNING </> ',
            Level::Error => '<fg=white;bg=red>  ERROR  </> ',
            Level::Critical => '<fg=white;bg=red>CRITICAL </> ',
            Level::Alert => '<fg=white;bg=blue>  ALERT  </> ',
            Level::Emergency => '<fg=white;bg=magenta>EMERGENCY</> ',
        };
    }
}
