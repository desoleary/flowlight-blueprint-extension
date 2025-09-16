<?php

namespace Tests\Unit\Config;

use Flowlight\Generator\Config\DtoConfig;

beforeEach(function () {
    $this->config = new DtoConfig('User', [
        'fields' => [
            'name' => ['type' => 'string'],
        ],
        'dto' => [
            'namespace' => 'Custom\\NS',
            'className' => 'MyUserDto',
            'extends' => 'App\\BaseDto',
        ],
    ], 'dto');
});

describe('shouldGenerate', function () {
    it('returns true when dto is true', function () {
        $c = new DtoConfig('User', ['dto' => true], 'dto');
        expect($c->shouldGenerate())->toBeTrue();
    });

    it('returns true when dto is a non-empty array', function () {
        $c = new DtoConfig('User', ['dto' => ['namespace' => 'X']], 'dto');
        expect($c->shouldGenerate())->toBeTrue();
    });

    it('returns false when dto is not defined', function () {
        $c = new DtoConfig('User', [], 'dto');
        expect($c->shouldGenerate())->toBeFalse();
    });
});

describe('getNamespace', function () {
    it('returns configured namespace', function () {
        expect($this->config->getNamespace())->toBe('Custom\\NS');
    });

    it('falls back to default namespace', function () {
        $c = new DtoConfig('User', [], 'dto');
        expect($c->getNamespace())->toBe('App\\Domain\\Users\\Data');
    });
});

describe('getClassName', function () {
    it('returns configured className', function () {
        expect($this->config->getClassName())->toBe('MyUserDto');
    });

    it('falls back to default className', function () {
        $c = new DtoConfig('User', [], 'dto');
        expect($c->getClassName())->toBe('UserData');
    });
});

describe('getExtendedClassName', function () {
    it('returns configured extended class', function () {
        expect($this->config->getExtendedClassName())->toBe('App\\BaseDto');
    });

    it('falls back to default extended class', function () {
        $c = new DtoConfig('User', [], 'dto');
        expect($c->getExtendedClassName())->toBe('Flowlight\\BaseData');
    });
});
