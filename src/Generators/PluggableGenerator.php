<?php

namespace Flowlight\Generator\Generators;

use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;

/**
 * Abstract base for pluggable generators (DTO, Organizer, etc.).
 *
 * Handles common logic for traversing the Blueprint tree, rendering
 * stubs, resolving paths, and writing files.
 *
 * @phpstan-type ApiDefinition array<string,mixed>
 * @phpstan-type ApiMap array<string,ApiDefinition>
 */
abstract class PluggableGenerator
{
    protected Filesystem $files;

    protected string $pluginKey;

    protected string $namespacePattern;

    protected string $classSuffix;

    /**
     * @param  array{key: string, namespace: string, suffix: string}  $pluginConfig
     */
    public function __construct(Filesystem $files, array $pluginConfig)
    {
        $this->files = $files;
        $this->pluginKey = $pluginConfig['key'];
        $this->namespacePattern = $pluginConfig['namespace'];
        $this->classSuffix = $pluginConfig['suffix'];
    }

    /**
     * Generate files based on the Blueprint tree.
     *
     * @return array{created: list<string>}
     */
    public function output(Tree $tree, ?string $stub = null): array
    {
        $output = ['created' => []];

        /** @var ApiMap $apis */
        $apis = $tree->toArray()['api'] ?? [];

        foreach ($apis as $entity => $definition) {
            /** @var string $entity */
            /** @var ApiDefinition $definition */
            if (empty($definition[$this->pluginKey])) {
                continue;
            }

            $namespace = str_replace('{{entity}}', $entity, $this->namespacePattern);
            $className = $entity.$this->classSuffix;

            $rendered = $this->populateStub(
                $stub ?? '',
                (string) $entity,
                $namespace,
                $className,
                (array) $definition
            );

            $path = $this->getPath($namespace, $className);

            if (! is_dir(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0777, true, true);
            }

            $this->files->put($path, $rendered);
            $output['created'][] = $path;
        }

        return $output;
    }

    /**
     * Implemented by child classes to replace stub placeholders.
     *
     * @param  ApiDefinition  $definition
     */
    abstract public function populateStub(
        string $stub,
        string $entity,
        string $namespace,
        string $className,
        array $definition
    ): string;

    protected function getPath(string $namespace, string $className): string
    {
        $base = config('blueprint.app_path', app_path());

        if (! is_string($base)) {
            throw new \RuntimeException('Invalid blueprint.app_path config: expected string');
        }

        $path = str_replace('\\', '/', $namespace);

        return $base.'/'.$path.'/'.$className.'.php';
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return ['api'];
    }
}
