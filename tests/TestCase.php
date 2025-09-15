<?php

namespace Tests;

use Flowlight\Generator\Providers\FlowlightServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 1);
        $this->app = new Application($basePath);

        // Register minimal core services your provider depends on
        $this->app->instance('config', new ConfigRepository([]));
        $this->app->instance('files', new Filesystem);

        // Register your provider
        $provider = new FlowlightServiceProvider($this->app);
        $provider->register();
        $provider->boot();
    }
}
