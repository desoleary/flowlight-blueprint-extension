<?php

namespace Tests\Unit\Providers;

use Blueprint\Blueprint;
use Flowlight\Generator\Providers\FlowlightServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application as LaravelApp;
use Mockery;

beforeEach(function () {
    // Fake Application by extending Container and adding runningInConsole()
    $this->app = new class(getcwd()) extends LaravelApp
    {
        public function runningInConsole(): bool
        {
            return true;
        }

        public function configPath($path = '')
        {
            return getcwd().'/tests/tmp/'.$path;
        }
    };

    // Bind config repository mock
    $this->config = Mockery::mock(ConfigRepository::class);
    $this->config->shouldReceive('set')->byDefault();
    $this->config->shouldReceive('get')->andReturn([])->byDefault();

    $this->app->instance('config', $this->config);
    $this->app->instance('files', new Filesystem);
    $this->app->instance(Blueprint::class, new Blueprint);

    $this->provider = new FlowlightServiceProvider($this->app);
});

afterEach(function () {
    Mockery::close();
});

describe('FlowlightServiceProvider', function () {
    it('merges configuration on register', function () {
        $this->provider->register();

        $path = realpath(__DIR__.'/../../../config/flowlight_blueprint.php');
        expect($path)->not->toBeFalse();
    });

    it('registers ApiGenerator into Blueprint on register', function () {
        $blueprint = $this->app->make(Blueprint::class);

        $spy = Mockery::spy($blueprint);
        $this->app->instance(Blueprint::class, $spy);

        $this->provider->register();

        $spy->shouldHaveReceived('registerGenerator')
            ->with(Mockery::type(\Flowlight\Generator\Generators\ApiGenerator::class));
    });
});
