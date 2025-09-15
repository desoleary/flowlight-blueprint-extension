<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;

/*
|--------------------------------------------------------------------------
| Test Application Class
|--------------------------------------------------------------------------
*/
class TestApplication extends Container implements Application
{
    protected $environment = 'testing';

    protected $version = '10.0.0';

    protected $booted = false;

    public function version()
    {
        return $this->version;
    }

    public function basePath($path = '')
    {
        return __DIR__.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    public function bootstrapPath($path = '')
    {
        return $this->basePath('bootstrap'.($path ? DIRECTORY_SEPARATOR.$path : $path));
    }

    public function configPath($path = '')
    {
        return $this->basePath('config'.($path ? DIRECTORY_SEPARATOR.$path : $path));
    }

    public function databasePath($path = '')
    {
        return $this->basePath('database'.($path ? DIRECTORY_SEPARATOR.$path : $path));
    }

    public function environmentPath()
    {
        return $this->basePath();
    }

    public function resourcePath($path = '')
    {
        return $this->basePath('resources'.($path ? DIRECTORY_SEPARATOR.$path : $path));
    }

    public function storagePath($path = '')
    {
        return $this->basePath('storage'.($path ? DIRECTORY_SEPARATOR.$path : $path));
    }

    public function environment(...$environments)
    {
        if (count($environments) > 0) {
            return in_array($this->environment, $environments);
        }

        return $this->environment;
    }

    public function runningInConsole()
    {
        return true;
    }

    public function runningUnitTests()
    {
        return true;
    }

    public function isDownForMaintenance()
    {
        return false;
    }

    public function registerConfiguredProviders()
    {
        // No-op for testing
    }

    public function register($provider, $force = false)
    {
        // No-op for testing
    }

    public function registerDeferredProvider($provider, $service = null)
    {
        // No-op for testing
    }

    public function resolveProvider($provider)
    {
        return $provider;
    }

    public function boot()
    {
        $this->booted = true;
    }

    public function booting($callback)
    {
        // No-op for testing
    }

    public function booted($callback)
    {
        // No-op for testing
    }

    public function bootstrapWith(array $bootstrappers)
    {
        // No-op for testing
    }

    public function getLocale()
    {
        return 'en';
    }

    public function getNamespace()
    {
        return 'App\\';
    }

    public function getProviders($provider)
    {
        return [];
    }

    public function hasBeenBootstrapped()
    {
        return $this->booted;
    }

    public function loadDeferredProviders()
    {
        // No-op for testing
    }

    public function setLocale($locale)
    {
        // No-op for testing
    }

    public function shouldSkipMiddleware()
    {
        return false;
    }

    public function terminate()
    {
        // No-op for testing
    }

    public function langPath($path = '')
    {
        // TODO: Implement langPath() method.
    }

    public function publicPath($path = '')
    {
        // TODO: Implement publicPath() method.
    }

    public function hasDebugModeEnabled()
    {
        // TODO: Implement hasDebugModeEnabled() method.
    }

    public function maintenanceMode()
    {
        // TODO: Implement maintenanceMode() method.
    }

    public function terminating($callback)
    {
        // TODO: Implement terminating() method.
    }
}
