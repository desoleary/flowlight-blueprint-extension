<?php

namespace Flowlight\Generator\Providers;

use Blueprint\Blueprint;
use Flowlight\Generator\Generators\ApiGenerator;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for Flowlight Blueprint extension.
 *
 * Registers config, publishes stubs, and hooks custom
 * generators into Blueprint.
 */
class FlowlightServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/flowlight_blueprint.php' => config_path('flowlight_blueprint.php'),
            ], 'flowlight-config');

            $this->publishes([
                __DIR__.'/../../stubs' => base_path('stubs/flowlight'),
            ], 'flowlight-stubs');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/flowlight_blueprint.php',
            'flowlight'
        );

        $this->app->singleton(ApiGenerator::class, function ($app): ApiGenerator {
            $files = $app->make('files');

            return new ApiGenerator($files);
        });

        $this->app->extend(Blueprint::class, function (Blueprint $blueprint, $app) {
            $blueprint->registerGenerator($app->make(ApiGenerator::class));

            return $blueprint;
        });
    }

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        $generators = array_values(config('flowlight.generators', []));

        return array_merge([Blueprint::class, ApiGenerator::class], $generators);
    }
}
