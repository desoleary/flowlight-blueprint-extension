<?php

namespace Flowlight\Generator\Generators;

use Flowlight\Generator\Config\ModelConfigWrapper;
use Flowlight\Generator\Fields\FieldCollection;
use Illuminate\Filesystem\Filesystem;

/**
 * DTO Generator for Flowlight.
 *
 * Uses PluggableGenerator to render DTO classes based on
 * api definitions with a `dto` section.
 */
class DtoGenerator extends PluggableGenerator
{
    public function __construct(Filesystem $files)
    {
        parent::__construct($files, [
            'key' => 'dto',
            'namespace' => 'App\\Domain\\{{modelName}}s\\Data',
            'suffix' => 'Data',
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
            ['{{ namespace }}', '{{ class }}', '{{ extends }}', '{{ properties }}'],
            [
                $namespace,
                $className,
                $extends ? "extends {$extends}" : '',
                $this->buildProperties($model->getFields()),
            ],
            $stub
        );
    }

    /**
     * Build property declarations for a DTO class.
     */
    protected function buildProperties(FieldCollection $fields): string
    {
        $props = '';

        foreach ($fields as $name => $field) {
            $type = $field->getType();
            $required = $field->isRequired();
            $nullable = $required ? '' : '?';

            $props .= "    /** @var {$nullable}{$type} */\n";
            $props .= "    public \${$name};\n\n";
        }

        return trim($props);
    }
}
