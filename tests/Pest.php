<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Pest\Expectation;
use PHPUnit\Framework\Assert;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Tests\TestCase;

require_once __DIR__.'/../vendor/autoload.php';
require __DIR__.'/bootstrap.php';
require __DIR__.'/TestCase.php';

uses(TestCase::class)->in('Unit', 'Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Custom extensions to Pest's expectation API for better diffs.
|
*/

/**
 * Helper to pretty print PHP arrays in short syntax ([ ]).
 */
$pretty = static function ($v): string {
    if (is_array($v)) {
        if ($v === []) {
            return '[]';
        }

        $exported = var_export($v, true);

        // Convert array(...) to [...]
        $exported = preg_replace('/^array \(/m', '[', $exported);
        $exported = preg_replace('/\)(,?)$/m', ']$1', $exported);

        return $exported;
    }

    return var_export($v, true);
};

/**
 * expect($actual)->toEqualDiff($expected)
 */
expect()->extend('toEqualDiff', function ($expected) use ($pretty): Expectation {
    $normalize = static function ($v) {
        if ($v instanceof Collection) {
            return $v->toArray();
        }
        if ($v instanceof Traversable) {
            return iterator_to_array($v);
        }

        return $v;
    };

    $actualNorm = $normalize($this->value);
    $expectedNorm = $normalize($expected);

    $isArrayLike = is_array($actualNorm) && is_array($expectedNorm);

    if (! $isArrayLike) {
        Assert::assertEquals($expected, $this->value);

        return $this;
    }

    if ($actualNorm === $expectedNorm) {
        Assert::assertSame($expectedNorm, $actualNorm);

        return $this;
    }

    $expectedStr = $pretty($expectedNorm);
    $actualStr = $pretty($actualNorm);

    $builder = new UnifiedDiffOutputBuilder("--- Expected\n+++ Actual\n");
    $differ = new Differ($builder);
    $diff = $differ->diff($expectedStr, $actualStr);

    $green = "\033[32m";
    $red = "\033[31m";
    $reset = "\033[0m";

    $message = <<<MSG
{$green}Expected: {$expectedStr}{$reset}

{$red}Actual: {$actualStr}{$reset}

Diff:
{$diff}
MSG;

    Assert::fail($message);
});

/**
 * Intercept `toEqual` for array/collection values.
 */
expect()->intercept(
    'toEqual',
    function ($value, $expected): bool {
        $norm = static function ($v) {
            if ($v instanceof Collection) {
                return $v->toArray();
            }
            if ($v instanceof Traversable) {
                return iterator_to_array($v);
            }

            return $v;
        };

        $a = $norm($value);
        $e = $norm($expected);

        return is_array($a) && is_array($e);
    },
    function ($expected) use ($pretty): void {
        $normalize = static function ($v) {
            if ($v instanceof Collection) {
                return $v->toArray();
            }
            if ($v instanceof Traversable) {
                return iterator_to_array($v);
            }

            return $v;
        };

        $actualNorm = $normalize($this->value);
        $expectedNorm = $normalize($expected);

        if ($actualNorm === $expectedNorm) {
            Assert::assertSame($expectedNorm, $actualNorm);

            return;
        }

        $expectedStr = $pretty($expectedNorm);
        $actualStr = $pretty($actualNorm);

        $builder = new UnifiedDiffOutputBuilder("--- Expected\n+++ Actual\n");
        $differ = new Differ($builder);
        $diff = $differ->diff($expectedStr, $actualStr);

        $green = "\033[32m";
        $red = "\033[31m";
        $reset = "\033[0m";

        $message = <<<MSG
{$green}Expected: {$expectedStr}{$reset}

{$red}Actual: {$actualStr}{$reset}

Diff:
{$diff}
MSG;

        Assert::fail($message);
    }
);

/**
 * Sets up a temp directory and optional random template file,
 * and returns a Pest fluent object.
 */
function setupTempDir(?string $templateContent = null): Fluent
{
    $files = new Filesystem;
    $tmpPath = sys_get_temp_dir().'/flowlight_test_app_'.uniqid('', true);
    $templateFile = null;

    if ($files->isDirectory($tmpPath)) {
        $files->deleteDirectory($tmpPath);
    }

    $files->makeDirectory($tmpPath, 0777, true);

    if ($templateContent !== null) {
        $filename = uniqid('stub_', true).'.stub';
        $templateFile = $tmpPath.'/'.$filename;
        $files->put($templateFile, $templateContent);
    }

    $fluent = fluent([
        'files' => $files,
        'tmpPath' => $tmpPath,
        'templateFile' => $templateFile,
    ]);

    $fluent->macro('cleanup', function () {
        if ($this->files->isDirectory($this->tmpPath)) {
            $this->files->deleteDirectory($this->tmpPath);
        }

        return $this;
    });

    $fluent->macro('withTemplate', function (string $content, ?string $ext = 'stub') {
        $filename = uniqid('stub_', true).'.'.$ext;
        $this->templateFile = $this->tmpPath.'/'.$filename;
        $this->files->put($this->templateFile, $content);

        return $this;
    });

    return $fluent;
}
/**
 * Cleans up a temp directory.
 */
function teardownTempDir(string $tmpPath): void
{
    $files = new Filesystem;
    if ($files->isDirectory($tmpPath)) {
        $files->deleteDirectory($tmpPath);
    }
}
