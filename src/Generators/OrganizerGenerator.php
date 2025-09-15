<?php

namespace Flowlight\Generator\Generators;

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
            'key' => 'organizers',
            'namespace' => 'App\\Domain\\{{entity}}s\\Organizers',
            'suffix' => 'Organizer',
        ]);
    }

    public function populateStub(
        string $stub,
        string $entity,
        string $namespace,
        string $className,
        array $definition
    ): string {
        return str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );
    }
}
