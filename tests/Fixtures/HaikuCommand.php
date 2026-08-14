<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger\Tests\Fixtures;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HaikuCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'haiku';

    /**
     * The console command description.
     */
    protected $description = 'Write a haiku to the logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::debug('To an old pond');
        Log::info('A frog leaps in');
        Log::notice('And the sound of the water');
        Log::warning('- Matsu Basho');

        return self::SUCCESS;
    }
}
