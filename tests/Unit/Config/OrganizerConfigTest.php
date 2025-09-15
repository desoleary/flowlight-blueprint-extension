<?php

use Flowlight\Generator\Config\OrganizerConfig;

describe('OrganizerConfig', function () {
    describe('__construct and getConfig', function () {
        it('stores and returns true config', function () {
            $config = new OrganizerConfig(true, 'User');
            expect($config->getConfig())->toBeTrue();
        });

        it('stores and returns false config', function () {
            $config = new OrganizerConfig(false, 'User');
            expect($config->getConfig())->toBeFalse();
        });

        it('stores and returns array config with all true', function () {
            $raw = ['create' => true, 'update' => true];
            $config = new OrganizerConfig($raw, 'User');
            expect($config->getConfig())->toBe($raw);
        });

        it('stores and returns array config with mixed values', function () {
            $raw = ['create' => true, 'read' => false, 'delete' => true];
            $config = new OrganizerConfig($raw, 'User');
            expect($config->getConfig())->toBe($raw);
        });

        it('stores and returns empty array config', function () {
            $raw = [];
            $config = new OrganizerConfig($raw, 'User');
            expect($config->getConfig())->toBe($raw);
        });
    });

    describe('getModelName', function () {
        it('returns the model name when config is true', function () {
            $config = new OrganizerConfig(true, 'Order');
            expect($config->getModelName())->toBe('Order');
        });

        it('returns the model name when config is false', function () {
            $config = new OrganizerConfig(false, 'Invoice');
            expect($config->getModelName())->toBe('Invoice');
        });

        it('returns the model name when config is array', function () {
            $config = new OrganizerConfig(['read' => true], 'Customer');
            expect($config->getModelName())->toBe('Customer');
        });
    });
});
