<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Tests\TestApplication;

require __DIR__.'/TestApplication.php';

/*
|--------------------------------------------------------------------------
| Setup Container and Facades
|--------------------------------------------------------------------------
*/
$container = new TestApplication;
Container::setInstance($container);

Facade::setFacadeApplication($container);

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
