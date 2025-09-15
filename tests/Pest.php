<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
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
