<?php

namespace Flowlight\Generator\Generators;

use Flowlight\Generator\Config\ModelConfigWrapper;
use Flowlight\Generator\Support\TemplateRenderer;
use Flowlight\Generator\Utils\LangUtils;
use Illuminate\Filesystem\Filesystem;

/**
 * DTO Generator for Flowlight.
 *
 * Renders `BaseData` subclasses from API definitions with a `dto` section.
 * Uses Mustache templates and delegates rendering to `TemplateRenderer`.
 */
class DtoGenerator extends PluggableGenerator
{
    protected TemplateRenderer $renderer;

    public function __construct(Filesystem $files)
    {
        parent::__construct($files, [
            'key' => 'dto',
            'namespace' => 'App\\Domain\\{{modelName}}s\\Data',
            'suffix' => 'Data',
        ]);

        $this->renderer = new TemplateRenderer;
    }

    /**
     * Populate the Mustache stub with model and field data.
     */
    public function populateStub(
        string $stub,
        string $modelName,
        string $namespace,
        string $className,
        ?string $parentClass,
        ModelConfigWrapper $model
    ): string {
        $parentClass = $parentClass ?? 'Flowlight\\BaseData';
        $parentClassName = LangUtils::toClassName($parentClass);

        $fields = [];
        foreach ($model->getFields() as $field) {
            $fields[] = $field->toRendererArray();
        }

        return $this->renderer->render($stub, [
            'namespace' => $namespace,
            'class' => $className,
            'parentClass' => $parentClass,
            'parentClassName' => $parentClassName,
            'fields' => $fields,
        ]);
    }
}
