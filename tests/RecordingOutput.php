<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger\Tests;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * A ConsoleOutput that records everything written to it instead of
 * sending it to STDOUT, so that tests can make assertions on it.
 */
final class RecordingOutput extends ConsoleOutput
{
    /**
     * Everything that has been written to this output.
     */
    private string $buffer = '';

    /**
     * ConsoleOutput auto-detects decoration from the real STDOUT of the
     * process running the tests when $decorated is left null, so
     * assertions on the recorded output would otherwise pass or fail
     * depending on whether the test suite happens to run attached to a
     * terminal. Default it to false so the buffer content is
     * deterministic; callers can still opt into decorated output.
     */
    public function __construct(
        int $verbosity = self::VERBOSITY_NORMAL,
        bool $decorated = false,
        ?OutputFormatterInterface $formatter = null,
    ) {
        parent::__construct($verbosity, $decorated, $formatter);
    }

    /**
     * Capture written messages rather than sending them to the terminal.
     */
    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message . ($newline ? PHP_EOL : '');
    }

    /**
     * Retrieve the recorded output.
     */
    public function contents(): string
    {
        return $this->buffer;
    }
}
