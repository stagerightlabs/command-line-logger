<?php

declare(strict_types=1);

namespace StageRightLabs\CommandLineLogger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use StageRightLabs\CommandLineLogger\Provider;

abstract class TestCase extends Orchestra
{
    /**
     * The package service providers that should be loaded.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            Provider::class,
        ];
    }
}
