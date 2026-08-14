<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger\Tests;

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
