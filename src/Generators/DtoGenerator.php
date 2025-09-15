<?php

namespace Flowlight\Generator\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Flowlight\Generator\Config\ModelConfigWrapper;
use Illuminate\Filesystem\Filesystem;

/**
 * DTO Generator for Flowlight API scaffolding.
 *
 * Implements Blueprint's {@see Generator} contract to generate DTO
 * (Data Transfer Object) classes for API models based on the parsed
 * Blueprint tree. It uses `ModelConfigWrapper` for normalized access
 * to model, field, and DTO configuration.
 */
class DtoGenerator implements Generator
{
    /**
     * Filesystem instance used for reading stubs and writing DTO files.
     */
    protected Filesystem $filesystem;

    /**
     * Create a new DTO generator.
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Get the generator types this class handles.
     *
     * @return list<string>
     */
    public function types(): array
    {
        return ['api'];
    }

    /**
     * Generate DTOs for models in the parsed Blueprint tree.
     *
     * @return array<string,string> Map of model name => generated DTO file path.
     */
    public function output(Tree $tree): array
    {
        $output = [];

        /**
         * @var array<string, array{
         *     table?: string,
         *     fields?: array<string, array{
         *         type?: string,
         *         required?: bool,
         *         length?: int,
         *         attribute?: string,
         *         rules?: list<string>,
         *         messages?: array<string, string>
         *     }>,
         *     dto?: array<string, mixed>|true,
         *     organizers?: array<string, bool>|bool
         * }> $models
         */
        $models = $tree->models();

        foreach ($models as $modelName => $modelConfig) {
            $wrapper = new ModelConfigWrapper($modelName, $modelConfig);

            if ($wrapper->shouldGenerateDto()) {
                $output[$modelName] = $this->generateDto($wrapper);
            }
        }

        return $output;
    }

    /**
     * Generate a DTO file for the given model.
     */
    public function generateDto(ModelConfigWrapper $wrapper): string
    {
        $dtoConfig = $wrapper->getDtoConfig();
        $stub = $this->filesystem->get(__DIR__.'/../stubs/dto.stub');

        $replacements = [
            '{{ namespace }}' => $dtoConfig->getNamespace(),
            '{{ class }}' => $dtoConfig->getClassName(),
            '{{ extends }}' => $dtoConfig->getExtends(),
            '{{ attributes }}' => $this->generateAttributes($wrapper),
            '{{ messages }}' => $this->generateMessages($wrapper),
            '{{ rules }}' => $this->generateRules($wrapper),
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $path = $this->getPath($dtoConfig->getNamespace(), $dtoConfig->getClassName());

        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, $content);

        return $path;
    }

    /**
     * Generate the `$attributes` array for the DTO.
     */
    public function generateAttributes(ModelConfigWrapper $wrapper): string
    {
        return $wrapper->getFields()
            ->map(fn ($field) => "        '{$field->getName()}' => '{$field->getAttributeLabel()}',")
            ->implode("\n");
    }

    /**
     * Generate the `$messages` array for the DTO.
     */
    public function generateMessages(ModelConfigWrapper $wrapper): string
    {
        $messages = [];

        foreach ($wrapper->getFields() as $field) {
            foreach ($field->getMessages() as $rule => $message) {
                $messages[] = "        '{$field->getName()}.{$rule}' => '{$message}',";
            }
        }

        foreach ($wrapper->getDtoConfig()->getCustomMessages() as $rule => $message) {
            $messages[] = "        '{$rule}' => '{$message}',";
        }

        return implode("\n", $messages);
    }

    /**
     * Generate the `$rules` array for the DTO.
     */
    public function generateRules(ModelConfigWrapper $wrapper): string
    {
        return $wrapper->getFields()
            ->map(function ($field) {
                $ruleString = implode(",\n                ", array_map(
                    fn (string $rule) => "'{$rule}'",
                    $field->getRules()
                ));

                return "            '{$field->getName()}' => [\n                {$ruleString}\n            ],";
            })
            ->implode("\n");
    }

    /**
     * Resolve the file path for the generated DTO.
     */
    public function getPath(string $namespace, string $className): string
    {
        $path = str_replace('App\\', 'app/', $namespace);
        $path = str_replace('\\', '/', $path);

        return base_path("{$path}/{$className}.php");
    }
}
