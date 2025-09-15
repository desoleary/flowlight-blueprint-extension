<?php

use Flowlight\Generator\Config\DtoConfig;

it('returns defaults when config is true', function () {
    $config = new DtoConfig(true, 'User');

    expect($config->getConfig())->toBeTrue()
        ->and($config->getModelName())->toBe('User')
        ->and($config->getNamespace())->toBe('App\\Domain\\Users\\Data')
        ->and($config->getClassName())->toBe('UserData')
        ->and($config->getExtends())->toBe('Flowlight\\BaseData')
        ->and($config->getCustomMessages())->toBeArray()->toBeEmpty();
});

it('returns overrides when config array provides values', function () {
    $config = new DtoConfig([
        'namespace' => 'Custom\\Namespace',
        'className' => 'CustomData',
        'extends' => 'Custom\\Base',
        'messages' => ['foo' => 'bar', 'baz' => 'qux'],
    ], 'Product');

    expect($config->getConfig())->toMatchArray([
        'namespace' => 'Custom\\Namespace',
        'className' => 'CustomData',
        'extends' => 'Custom\\Base',
        'messages' => ['foo' => 'bar', 'baz' => 'qux'],
    ]);

    expect($config->getModelName())->toBe('Product');
    expect($config->getNamespace())->toBe('Custom\\Namespace');
    expect($config->getClassName())->toBe('CustomData');
    expect($config->getExtends())->toBe('Custom\\Base');
    expect($config->getCustomMessages())->toMatchArray(['foo' => 'bar', 'baz' => 'qux']);
});

it('falls back to defaults when config array is missing keys', function () {
    $config = new DtoConfig([], 'Order');

    expect($config->getNamespace())->toBe('App\\Domain\\Orders\\Data')
        ->and($config->getClassName())->toBe('OrderData')
        ->and($config->getExtends())->toBe('Flowlight\\BaseData')
        ->and($config->getCustomMessages())->toBeArray()->toBeEmpty();
});

it('falls back to defaults when config values are invalid types', function () {
    $config = new DtoConfig([
        'namespace' => 123,
        'className' => null,
        'extends' => ['bad'],
        'messages' => ['foo' => 1], // not strictly string values
    ], 'Invoice');

    expect($config->getNamespace())->toBe('App\\Domain\\Invoices\\Data')
        ->and($config->getClassName())->toBe('InvoiceData')
        ->and($config->getExtends())->toBe('Flowlight\\BaseData')
        ->and($config->getCustomMessages())->toBeArray()->toEqual([]);
});
