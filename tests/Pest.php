<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Pest\Expectation;
use PHPUnit\Framework\Assert;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

require_once __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Minimal Laravel-like Container + Helpers
|--------------------------------------------------------------------------
*/
$container = new Container;
Container::setInstance($container);

// Cast to Application contract for type checkers
/** @var Application $app */
$app = $container;

Facade::setFacadeApplication($app);

$configRepo = new Repository([
    'data' => [
        'max_transformation_depth' => 64,
        'throw_when_max_transformation_depth_reached' => false,
        'date_format' => 'Y-m-d\TH:i:sP',
        'casts' => [],
        'transformers' => [],
        'wrap' => null,
        'enabled_casters' => [],
    ],
]);
$container->instance('config', $configRepo);

$translator = new Translator(new ArrayLoader, 'en');
$validatorFactory = new ValidatorFactory($translator);

$container->instance(ValidatorFactory::class, $validatorFactory);
$container->instance('validator', $validatorFactory);

// global helpers
if (! function_exists('app')) {
    function app(?string $abstract = null, array $parameters = [])
    {
        $container = Container::getInstance();

        return $abstract === null
            ? $container
            : $container->make($abstract, $parameters);
    }
}

if (! function_exists('config')) {
    function config($key = null, $default = null)
    {
        /** @var Repository $repo */
        $repo = app('config');

        if ($key === null) {
            return $repo;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $repo->set($k, $v);
            }

            return true;
        }

        return $repo->get($key, $default);
    }
}

if (! function_exists('validator')) {
    function validator(array $data = [], array $rules = [], array $messages = [], array $attributes = [])
    {
        /** @var ValidatorFactory $vf */
        $vf = app(ValidatorFactory::class);

        return $vf->make($data, $rules, $messages, $attributes);
    }
}

/*
|--------------------------------------------------------------------------
| Custom Pest Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toEqualDiff', function ($expected): Expectation {
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

    if (! is_array($actualNorm) || ! is_array($expectedNorm)) {
        Assert::assertEquals($expected, $this->value);

        return $this;
    }

    if ($actualNorm === $expectedNorm) {
        Assert::assertSame(
            json_encode($expectedNorm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($actualNorm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $this;
    }

    $pretty = static fn ($v) => json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $builder = new UnifiedDiffOutputBuilder("--- Expected\n+++ Actual\n");
    $diff = (new Differ($builder))->diff($pretty($expectedNorm), $pretty($actualNorm));

    Assert::fail("Diff:\n{$diff}");
});

/*
|--------------------------------------------------------------------------
| Test Case Binding (optional)
|--------------------------------------------------------------------------
|
| If you want to bind Feature tests to a base TestCase class:
|
| uses(Tests\TestCase::class)->in('Feature');
|
*/
