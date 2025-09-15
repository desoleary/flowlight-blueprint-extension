<?php

namespace Flowlight\Generator\Generators;

use Illuminate\Filesystem\Filesystem;

/**
 * DTO Generator for Flowlight.
 *
 * Uses PluggableGenerator to render DTO classes based on
 * api definitions with a `dto` section.
 *
 * @phpstan-import-type ApiDefinition from PluggableGenerator
 */
class DtoGenerator extends PluggableGenerator
{
    public function __construct(Filesystem $files)
    {
        parent::__construct($files, [
            'key' => 'dto',
            'namespace' => 'App\\Domain\\{{entity}}s\\Data',
            'suffix' => 'Data',
        ]);
    }

    /**
     * @param  ApiDefinition  $definition
     */
    public function populateStub(
        string $stub,
        string $entity,
        string $namespace,
        string $className,
        array $definition
    ): string {
        $dtoConfig = is_array($definition['dto'] ?? null) ? $definition['dto'] : [];
        $extends = isset($dtoConfig['extends']) && is_string($dtoConfig['extends'])
            ? $dtoConfig['extends']
            : null;

        /** @var array<string,mixed> $fields */
        $fields = is_array($definition['fields'] ?? null)
            ? $definition['fields']
            : [];

        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ extends }}', '{{ properties }}'],
            [
                $namespace,
                $className,
                $extends ? "extends {$extends}" : '',
                $this->buildProperties($fields),
            ],
            $stub
        );
    }

    /**
     * Build property declarations for a DTO class.
     *
     * @param  array<string,mixed>  $fields
     */
    public function buildProperties(array $fields): string
    {
        $props = '';
        foreach ($fields as $name => $meta) {
            if (! is_array($meta)) {
                continue; // skip invalid entries
            }

            $type = isset($meta['type']) && is_string($meta['type'])
                ? $meta['type']
                : 'mixed';

            $required = isset($meta['required']) && $meta['required'] === true;
            $nullable = $required ? '' : '?';

            $props .= "    /** @var {$nullable}{$type} */\n";
            $props .= "    public \${$name};\n\n";
        }

        return trim($props);
    }
}
