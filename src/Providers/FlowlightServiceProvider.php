<?php

namespace Flowlight\Generator\Providers;

use Blueprint\Blueprint;
use Flowlight\Generator\Generators\DtoGenerator;
use Flowlight\Generator\Generators\OrganizerGenerator;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FlowlightServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/field-types.php' => config_path('flowlight.php'),
            ], 'flowlight-config');

            $this->publishes([
                __DIR__.'/../../stubs' => base_path('stubs/flowlight'),
            ], 'flowlight-stubs');
        }

        $this->app->extend(Blueprint::class, function (Blueprint $blueprint, Application $app) {
            /** @var \Illuminate\Filesystem\Filesystem $files */
            $files = $app->make('files');

            $blueprint->registerGenerator(new DtoGenerator($files));
            $blueprint->registerGenerator(new OrganizerGenerator($files));

            return $blueprint;
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/field-types.php', 'flowlight'
        );
    }
}
