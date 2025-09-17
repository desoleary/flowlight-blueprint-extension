<?php

namespace Tests\Support;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

/**
 * An in-memory fake filesystem for testing.
 *
 * - Generated files are stored in memory.
 * - Reads/writes for stubs or other existing files fall back to the real disk.
 */
final class InMemoryFilesystem extends Filesystem
{
    /**
     * In-memory file storage.
     *
     * @var array<string,string> Map of path => file contents
     */
    private array $storage = [];

    /**
     * In-memory directory storage.
     *
     * @var array<string,bool> Map of directory path => exists flag
     */
    private array $directories = [];

    public function exists($path): bool
    {
        return isset($this->storage[$path])
            || isset($this->directories[$path])
            || parent::exists($path);
    }

    public function get($path, $lock = false): string
    {
        if (isset($this->storage[$path])) {
            return $this->storage[$path];
        }

        return parent::get($path, $lock);
    }

    public function put($path, $contents, $lock = false): bool
    {
        $dir = \dirname($path);
        $this->directories[$dir] = true;
        $this->storage[$path] = (string) $contents;

        return true;
    }

    public function makeDirectory($path, $mode = 0777, $recursive = false, $force = false): bool
    {
        $this->directories[$path] = true;

        return true;
    }

    public function isDirectory($path): bool
    {
        return isset($this->directories[$path]) || parent::isDirectory($path);
    }

    public function deleteDirectory($directory, $preserve = false): bool
    {
        foreach ($this->storage as $path => $contents) {
            if (\str_starts_with($path, $directory)) {
                unset($this->storage[$path]);
            }
        }
        unset($this->directories[$directory]);

        return true;
    }

    /**
     * Get all files under a directory in memory.
     *
     * @param  string  $directory  Directory prefix
     * @param  bool  $hidden  Ignored in-memory
     * @return array<string,string> Map of path => contents
     */
    public function allFiles($directory, $hidden = false): array
    {
        $results = [];

        foreach ($this->storage as $path => $contents) {
            if (\str_starts_with($path, rtrim($directory, '/'))) {
                $results[$path] = $contents;
            }
        }

        return $results;
    }

    /**
     * Return all in-memory files regardless of directory.
     *
     * @return array<string,string> Map of path => contents
     */
    public function getStorage(): array
    {
        return $this->storage;
    }

    /**
     * Get stub file contents from the project's stubs dir.
     *
     * Accepts either just a filename (e.g. 'dto.stub.hbs')
     * or a relative path (e.g. 'subdir/custom.stub.hbs').
     */
    public function getStub(string $relativePath): string
    {
        $stubBase = dirname(__DIR__, 2).'/stubs';
        $fullPath = $stubBase.'/'.ltrim($relativePath, '/');

        try {
            return parent::get($fullPath);
        } catch (FileNotFoundException $e) {
            \PHPUnit\Framework\Assert::fail(
                "❌ Stub not found: {$relativePath} (looked in {$fullPath})"
            );
        }
    }
}
