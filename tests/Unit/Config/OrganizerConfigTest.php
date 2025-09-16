<?php

namespace Tests\Unit\Config;

use Flowlight\Generator\Config\OrganizerConfig;

beforeEach(function () {
    $this->config = new OrganizerConfig('User', [
        'organizers' => [
            'create' => true,
            'read' => false,
            'list' => true,
        ],
    ], 'organizers');
});

describe('shouldGenerate', function () {
    it('returns true when organizers is true', function () {
        $c = new OrganizerConfig('User', ['organizers' => true], 'organizers');
        expect($c->shouldGenerate())->toBeTrue();
    });

    it('returns true when organizers is a non-empty array', function () {
        $c = new OrganizerConfig('User', ['organizers' => ['create' => true]], 'organizers');
        expect($c->shouldGenerate())->toBeTrue();
    });

    it('returns false when organizers not defined', function () {
        $c = new OrganizerConfig('User', [], 'organizers');
        expect($c->shouldGenerate())->toBeFalse();
    });
});

describe('getNamespace', function () {
    it('returns configured namespace', function () {
        $c = new OrganizerConfig('User', ['organizers' => ['namespace' => 'Custom\\NS']], 'organizers');
        expect($c->getNamespace())->toBe('Custom\\NS');
    });

    it('falls back to default namespace', function () {
        $c = new OrganizerConfig('User', [], 'organizers');
        expect($c->getNamespace())->toBe('App\\Domain\\Users\\Organizers');
    });
});

describe('getClassName', function () {
    it('returns configured className', function () {
        $c = new OrganizerConfig('User', ['organizers' => ['className' => 'CustomOrganizer']], 'organizers');
        expect($c->getClassName())->toBe('CustomOrganizer');
    });

    it('falls back to default className', function () {
        $c = new OrganizerConfig('User', [], 'organizers');
        expect($c->getClassName())->toBe('UserOrganizer');
    });
});

describe('getExtendedClassName', function () {
    it('returns configured extended class', function () {
        $c = new OrganizerConfig('User', ['organizers' => ['extends' => 'BaseOrganizer']], 'organizers');
        expect($c->getExtendedClassName())->toBe('BaseOrganizer');
    });

    it('falls back to default extended class when not configured', function () {
        $c = new OrganizerConfig('User', [], 'organizers');
        expect($c->getExtendedClassName())->toBe('Flowlight\\LightService\\Organizer');
    });
});
