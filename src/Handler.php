<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger;

use Illuminate\Container\Container;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class Handler implements HandlerInterface
{
    /**
     * The container binding holding the output of the running command.
     */
    private const OUTPUT = 'command.log.output';

    /**
     * @see Symfony\Component\Console\Formatter\OutputFormatterInterface
     */
    private const VERBOSITY_SILENT = 8;
    private const VERBOSITY_QUIET = 16;
    private const VERBOSITY_NORMAL = 32;
    private const VERBOSITY_VERBOSE = 64;
    private const VERBOSITY_VERY_VERBOSE = 128;
    private const VERBOSITY_DEBUG = 256;

    public function __construct(
        private Container $container,
        private CliDumper $dumper,
        private VarCloner $cloner
    ) {}

    /**
     * Resolve the output of the command that is currently running.
     *
     * There may not be one: the console channel can be part of a log stack
     * that is also used outside of a command, and the subscriber releases
     * the output once a command has finished. Resolving this per record
     * rather than caching it also keeps the handler correct when a log
     * channel outlives the command that created it.
     */
    protected function output(): ?OutputInterface
    {
        if (! $this->container->bound(self::OUTPUT)) {
            return null;
        }

        $output = $this->container->get(self::OUTPUT);

        return $output instanceof OutputInterface ? $output : null;
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
        $output = $this->output();

        return $output !== null && $this->shouldBeHandled($record, $output);
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
        $output = $this->output();

        if ($output !== null && $this->shouldBeHandled($record, $output)) {
            $this->write($record, $output);
        }

        // Return false to allow bubbling
        return false;
    }

    /**
     * Use verbosity level to determine if a message should be logged.
     */
    protected function shouldBeHandled(LogRecord $record, OutputInterface $output): bool
    {
        return match ($output->getVerbosity()) {
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
    protected function write(LogRecord $record, OutputInterface $output): void
    {
        // Prepare a message prefix based on log level
        $level = match ($output->isDecorated()) {
            true => $this->getDecoratedMessagePrefix($record->level),
            false => '[' . strtoupper($record->level->name) . '] ',
        };

        // Write the message to console output
        $output->writeln($level . $record->message);

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
