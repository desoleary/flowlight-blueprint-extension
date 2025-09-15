<?php

namespace Flowlight\Generator\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;

/**
 * Central API generator that delegates work to pluggable sub-generators
 * (e.g., DtoGenerator, OrganizerGenerator).
 *
 * Stub templates are resolved dynamically based on configuration:
 *
 * flowlight.generators:
 *   dto: Flowlight\Generator\Generators\DtoGenerator
 *   organizers: Flowlight\Generator\Generators\OrganizerGenerator
 *
 * flowlight.stubs:
 *   dto: dto.stub
 *   organizers: organizer.stub
 */
class ApiGenerator implements Generator
{
    protected Filesystem $files;

    /**
     * @var array<string, Generator>
     */
    protected array $generators = [];

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        /** @var array<string, class-string<Generator>> $configured */
        $configured = config('flowlight.generators', []);

        foreach ($configured as $key => $class) {
            $this->generators[$key] = new $class($files);
        }
    }

    /**
     * Generate API-related files by delegating to configured generators.
     *
     * @return array<string, list<string>>
     */
    public function output(Tree $tree): array
    {
        $output = ['created' => []];

        foreach ($this->generators as $key => $generator) {
            $stub = $this->resolveStub($key);

            /** @var array<string, list<string>> $result */
            $result = method_exists($generator, 'outputWithStub')
                ? $generator->outputWithStub($tree, $stub)
                : $generator->output($tree);

            foreach ($result as $status => $files) {
                // At this point $files is guaranteed list<string>
                $output[$status] = array_merge($output[$status] ?? [], $files);
            }
        }

        return $output;
    }

    /**
     * Resolve stub contents for a given generator key.
     *
     * @throws \RuntimeException
     */
    protected function resolveStub(string $key): string
    {
        $stubFile = config("flowlight.stubs.$key");
        if (! is_string($stubFile) || $stubFile === '') {
            throw new \RuntimeException("No stub configured for generator [$key]");
        }

        $custom = base_path("stubs/flowlight/{$stubFile}");
        $default = dirname(__DIR__, 2)."/stubs/{$stubFile}";

        if ($this->files->exists($custom)) {
            return $this->files->get($custom);
        }

        if ($this->files->exists($default)) {
            return $this->files->get($default);
        }

        throw new \RuntimeException("Stub file not found for [$key]: $stubFile");
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return ['api'];
    }
}
