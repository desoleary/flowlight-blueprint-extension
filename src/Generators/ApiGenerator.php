<?php

namespace Flowlight\Generator\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Flowlight\Generator\Config\ModelConfigWrapper;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class ApiGenerator implements Generator
{
    protected Filesystem $files;

    /** @var array<string, PluggableGenerator> */
    protected array $generators = [];

    /** @var array<string, array{class: class-string<PluggableGenerator>, stub: string, config_class: class-string<ModelConfigWrapper>}> */
    protected array $configured = [];

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->configured = $this->getConfig();

        foreach ($this->configured as $key => $cfg) {
            $class = $this->requireConfigValue($cfg, 'class', $key);
            $stub = $this->requireConfigValue($cfg, 'stub', $key);
            $configClass = $this->requireConfigValue($cfg, 'config_class', $key);

            /** @var class-string<PluggableGenerator> $class */
            $this->generators[$key] = new $class($files, [
                'key' => $key,
                'stub' => $stub,
                'config_class' => $configClass,
            ]);
        }
    }

    /**
     * Generate API-related files for all models in the given tree
     * by delegating to each configured sub-generator.
     *
     * @return array<string, list<string>> Map of statuses to file paths.
     */
    public function output(Tree $tree): array
    {
        /** @var array<string, list<string>> $output */
        $output = ['created' => []];

        /** @var array<string, array<string, mixed>> $models */
        $models = $tree->toArray()['api'] ?? [];

        foreach ($models as $modelName => $definition) {
            foreach ($this->generators as $key => $generator) {
                $stub = $this->resolveStub($key);
                $configClass = $this->getConfigClass($key);

                $partialOutput = $generator->output(
                    new $configClass($modelName, $definition, $key),
                    $stub
                );

                foreach ($partialOutput as $status => $files) {
                    /** @var list<string> $files */
                    $output[$status] = array_merge($output[$status] ?? [], $files);
                }
            }
        }

        return $output;
    }

    protected function resolveStub(string $key): string
    {
        $stubFile = $this->configured[$key]['stub'] ?? null;
        if (! is_string($stubFile) || $stubFile === '') {
            throw new RuntimeException("No stub configured for generator [$key]");
        }

        $custom = base_path("stubs/flowlight/{$stubFile}");
        $default = dirname(__DIR__, 2)."/stubs/{$stubFile}";

        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        if ($this->files->exists($default)) {
            return $this->files->get($default);
        }

        throw new RuntimeException("Stub file not found for [$key]: $stubFile");
    }

    /** @return class-string<ModelConfigWrapper> */
    protected function getConfigClass(string $key): string
    {
        $cfg = $this->configured[$key] ?? [];

        /** @var class-string<ModelConfigWrapper> */
        return $this->requireConfigValue($cfg, 'config_class', $key);
    }

    /**
     * Ensure a required config value is present and non-empty.
     *
     * @param  array<string, string>  $cfg
     *
     * @throws \LogicException
     */
    private function requireConfigValue(array $cfg, string $requiredKey, string $generatorKey): string
    {
        $value = $cfg[$requiredKey] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new \LogicException(
                static::class.": Missing required config key [{$requiredKey}] for generator [{$generatorKey}]"
            );
        }

        return $value;
    }

    /**
     * @return array<string, array{class: class-string<PluggableGenerator>, stub: string, config_class: class-string<ModelConfigWrapper>}>
     */
    protected function getConfig(): array
    {
        /** @var array<string, array{class: class-string<PluggableGenerator>, stub: string, config_class: class-string<ModelConfigWrapper>}> */
        return config('flowlight.generators', []);
    }

    /** @return list<string> */
    public function types(): array
    {
        return ['api'];
    }
}
