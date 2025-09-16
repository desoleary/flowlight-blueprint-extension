<?php

namespace Flowlight\Generator\Generators;

use Flowlight\Generator\Config\ModelConfigWrapper;
use Illuminate\Filesystem\Filesystem;

/**
 * Abstract base for pluggable generators (DTO, Organizer, etc.).
 *
 * Handles common logic for:
 * - Checking whether a model should be generated
 * - Resolving namespace, class, and extends values
 * - Rendering stubs
 * - Creating directories and writing files
 *
 * Child classes must implement {@see populateStub()} to replace placeholders
 * in their stub template.
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
     * Generate files based on the provided model wrapper.
     *
     * @return array{created: list<string>}
     */
    public function output(ModelConfigWrapper $model, string $stub): array
    {
        $output = ['created' => []];

        if (! $model->shouldGenerate()) {
            return $output;
        }

        $modelName = $model->getModelName();

        /** @var array<string,mixed> $cfg */
        $cfg = config("flowlight.generators.{$this->pluginKey}", []);

        $namespace = $this->resolveRequiredVariable($cfg, 'namespace', $model->getNamespace());
        $className = $this->resolveRequiredVariable($cfg, 'className', $model->getClassName());
        $extends = $this->resolveOptionalVariable($cfg, 'extends', $model->getExtendedClassName());

        $rendered = $this->populateStub(
            $stub,
            $modelName,
            $namespace,
            $className,
            $extends,
            $model
        );

        $path = $this->getPath($namespace, $className);

        if (! is_dir(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        $this->files->put($path, $rendered);
        $output['created'][] = $path;

        return $output;
    }

    /**
     * @codeCoverageIgnore This is an abstract method
     *
     * Implemented by child classes to replace stub placeholders.
     *
     * @param  string  $stub  The raw stub template contents
     * @param  string  $modelName  The entity/model name (e.g. "User")
     * @param  string  $namespace  The resolved namespace
     * @param  string  $className  The resolved class name
     * @param  string|null  $extends  Parent class, or null
     * @param  ModelConfigWrapper  $model  Model wrapper containing field/config info
     */
    abstract public function populateStub(
        string $stub,
        string $modelName,
        string $namespace,
        string $className,
        ?string $extends,
        ModelConfigWrapper $model
    ): string;

    /**
     * Resolve a required config variable, ensuring it is a non-empty string.
     *
     * @param  array<string,mixed>  $cfg
     *
     * @throws \LogicException if the value is missing or invalid
     */
    protected function resolveRequiredVariable(array $cfg, string $key, ?string $fallback): string
    {
        /** @var array<string,mixed> $vars */
        $vars = is_array($cfg['variables'] ?? null) ? $cfg['variables'] : [];

        $value = $vars[$key] ?? $fallback;

        if (! is_string($value) || trim($value) === '') {
            throw new \LogicException("Missing required config value [$key] for generator [{$this->pluginKey}]");
        }

        return $value;
    }

    /**
     * Resolve an optional config variable, allowing string or null.
     *
     * @param  array<string,mixed>  $cfg
     *
     * @throws \LogicException if the value is set but not a string
     */
    protected function resolveOptionalVariable(array $cfg, string $key, ?string $fallback): ?string
    {
        /** @var array<string,mixed> $vars */
        $vars = is_array($cfg['variables'] ?? null) ? $cfg['variables'] : [];

        $value = $vars[$key] ?? $fallback;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new \LogicException("Invalid config value for [$key] in generator [{$this->pluginKey}]");
        }

        return $value;
    }

    /**
     * Build the file path where the generated class should be written.
     */
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
     * Return the types supported by this generator.
     *
     * @return list<string>
     */
    public function types(): array
    {
        return ['api'];
    }
}
