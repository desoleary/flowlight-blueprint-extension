<?php

namespace Flowlight\Generator\Generators;

use Flowlight\Generator\Config\ModelConfigWrapper;
use Illuminate\Filesystem\Filesystem;

/**
 * Organizer Generator for Flowlight.
 *
 * Uses PluggableGenerator to render organizer classes when
 * api definitions include `organizers: true`.
 */
class OrganizerGenerator extends PluggableGenerator
{
    public function __construct(Filesystem $files)
    {
        parent::__construct($files, [
            'key' => 'organizer',
            'namespace' => 'App\\Domain\\{{modelName}}s\\Organizers',
            'suffix' => 'Organizer',
        ]);
    }

    public function populateStub(
        string $stub,
        string $modelName,
        string $namespace,
        string $className,
        ?string $extends,
        ModelConfigWrapper $model
    ): string {
        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ extends }}'],
            [
                $namespace,
                $className,
                $extends ? "extends {$extends}" : '',
            ],
            $stub
        );
    }
}
