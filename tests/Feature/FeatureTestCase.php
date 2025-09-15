<?php

namespace Tests\Feature;

use Flowlight\Generator\Providers\FlowlightServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class FeatureTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FlowlightServiceProvider::class,
        ];
    }
}
