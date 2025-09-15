<?php

namespace Flowlight\Generator\Providers;

use Blueprint\Blueprint;
use Flowlight\Generator\Generators\DtoGenerator;
use Flowlight\Generator\Generators\OrganizerGenerator;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for Flowlight code generation.
 *
 * This provider integrates Flowlight’s DTO and Organizer generators
 * into Laravel and Blueprint. It handles:
 *
 * - Publishing configuration (`flowlight.php`) and stubs
 *   (`stubs/flowlight`) when running in console.
 * - Extending Blueprint to register custom generators:
 *   {@see DtoGenerator} and {@see OrganizerGenerator}.
 * - Merging Flowlight’s default field types config into the
 *   application configuration.
 */
class FlowlightServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * - Publishes Flowlight config and stubs when running in console.
     * - Extends Blueprint to add Flowlight generators.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish default config
            $this->publishes([
                __DIR__.'/../../config/field-types.php' => config_path('flowlight.php'),
            ], 'flowlight-config');

            // Publish stubs for customization
            $this->publishes([
                __DIR__.'/../../stubs' => base_path('stubs/flowlight'),
            ], 'flowlight-stubs');
        }

        // Extend Blueprint with Flowlight generators
        $this->app->extend(Blueprint::class, function (Blueprint $blueprint, Application $app) {
            /** @var \Illuminate\Filesystem\Filesystem $files */
            $files = $app->make('files');

            $blueprint->registerGenerator(new DtoGenerator($files));
            $blueprint->registerGenerator(new OrganizerGenerator($files));

            return $blueprint;
        });
    }

    /**
     * Register any application services.
     *
     * - Merges Flowlight’s default configuration into the app config.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/field-types.php',
            'flowlight'
        );
    }
}
