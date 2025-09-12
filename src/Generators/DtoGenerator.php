<?php

namespace Flowlight\Generator\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Flowlight\Generator\Config\ModelConfigWrapper;
use Illuminate\Filesystem\Filesystem;

/**
 * @phpstan-import-type ModelConfigArray from ModelConfigWrapper
 */
class DtoGenerator implements Generator
{
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return ['api'];
    }

    /**
     * @return array<string,string> modelName => generated path
     */
    public function output(Tree $tree): array
    {
        $output = [];

        /** @var array<string, ModelConfigArray> $models */
        $models = $tree->models();

        foreach ($models as $modelName => $modelConfig) {
            // @phpstan-var ModelConfigArray $modelConfig
            $wrapper = new ModelConfigWrapper($modelName, $modelConfig);

            if ($wrapper->shouldGenerateDto()) {
                $output[$modelName] = $this->generateDto($wrapper);
            }
        }

        return $output;
    }

    /**
     * Generate a DTO file and return the path.
     */
    protected function generateDto(ModelConfigWrapper $wrapper): string
    {
        $dtoConfig = $wrapper->getDtoConfig();
        $stub = $this->filesystem->get(__DIR__.'/../stubs/dto.stub');

        /** @var array<string,string> $replacements */
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

    protected function generateAttributes(ModelConfigWrapper $wrapper): string
    {
        return $wrapper->getFields()
            ->map(fn ($field) => "        '{$field->getName()}' => '{$field->getAttributeLabel()}',")
            ->implode("\n");
    }

    protected function generateMessages(ModelConfigWrapper $wrapper): string
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

    protected function generateRules(ModelConfigWrapper $wrapper): string
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

    protected function getPath(string $namespace, string $className): string
    {
        $path = str_replace('App\\', 'app/', $namespace);
        $path = str_replace('\\', '/', $path);

        return base_path("{$path}/{$className}.php");
    }
}
