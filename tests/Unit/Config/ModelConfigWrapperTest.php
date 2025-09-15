<?php

use Flowlight\Generator\Config\DtoConfig;
use Flowlight\Generator\Config\FieldConfig;
use Flowlight\Generator\Config\ModelConfigWrapper;

describe('ModelConfigWrapper', function () {
    describe('getModelName', function () {
        it('returns the model name', function () {
            $wrapper = new ModelConfigWrapper('User', []);
            expect($wrapper->getModelName())->toBe('User');
        });
    });

    describe('getTableName', function () {
        it('returns default table name when not overridden', function () {
            $wrapper = new ModelConfigWrapper('User', []);
            expect($wrapper->getTableName())->toBe('users');
        });

        it('returns overridden table name when provided', function () {
            $wrapper = new ModelConfigWrapper('User', ['table' => 'custom_users']);
            expect($wrapper->getTableName())->toBe('custom_users');
        });
    });

    describe('shouldGenerateDto', function () {
        it('returns true when dto config exists', function () {
            $wrapper = new ModelConfigWrapper('User', ['dto' => true]);
            expect($wrapper->shouldGenerateDto())->toBeTrue();
        });

        it('returns false when fields exist without dto config', function () {
            $wrapper = new ModelConfigWrapper('User', [
                'fields' => ['name' => ['type' => 'string']],
            ]);
            expect($wrapper->shouldGenerateDto())->toBeFalse();
        });

        it('returns false when no dto config or fields exist', function () {
            $wrapper = new ModelConfigWrapper('User', []);
            expect($wrapper->shouldGenerateDto())->toBeFalse();
        });
    });

    describe('getDtoConfig', function () {
        it('returns a DtoConfig instance', function () {
            $wrapper = new ModelConfigWrapper('User', ['dto' => true]);
            expect($wrapper->getDtoConfig())->toBeInstanceOf(DtoConfig::class);
        });
    });

    describe('getFields', function () {
        it('returns a collection of FieldConfig objects', function () {
            $wrapper = new ModelConfigWrapper('User', [
                'fields' => [
                    'email' => ['type' => 'string', 'required' => true],
                ],
            ]);

            $fields = $wrapper->getFields();

            expect($fields)->toHaveCount(1)
                ->and($fields->get('email'))->toBeInstanceOf(FieldConfig::class);
        });

        it('returns an empty collection when no fields are configured', function () {
            $wrapper = new ModelConfigWrapper('User', []);
            expect($wrapper->getFields())->toHaveCount(0);
        });
    });

    describe('getField', function () {
        it('returns a specific FieldConfig when it exists', function () {
            $wrapper = new ModelConfigWrapper('User', [
                'fields' => ['name' => ['type' => 'string']],
            ]);

            expect($wrapper->getField('name'))->toBeInstanceOf(FieldConfig::class);
        });

        it('returns null when the field does not exist', function () {
            $wrapper = new ModelConfigWrapper('User', []);
            expect($wrapper->getField('missing'))->toBeNull();
        });
    });

    describe('shouldGenerateOrganizers', function () {
        it('returns true when organizers is true', function () {
            $wrapper = new ModelConfigWrapper('User', ['organizers' => true]);
            expect($wrapper->shouldGenerateOrganizers())->toBeTrue();
        });

        it('returns false when organizers is false', function () {
            $wrapper = new ModelConfigWrapper('User', ['organizers' => false]);
            expect($wrapper->shouldGenerateOrganizers())->toBeFalse();
        });

        it('returns false when organizers key is missing', function () {
            $wrapper = new ModelConfigWrapper('User', []);
            expect($wrapper->shouldGenerateOrganizers())->toBeFalse();
        });
    });

    describe('getOrganizerTypes', function () {
        it('returns default CRUD+list when organizers is true', function () {
            $wrapper = new ModelConfigWrapper('User', ['organizers' => true]);
            expect($wrapper->getOrganizerTypes())->toBe(['create', 'read', 'update', 'delete', 'list']);
        });

        it('returns only enabled organizer types from array config', function () {
            $wrapper = new ModelConfigWrapper('User', [
                'organizers' => [
                    'create' => true,
                    'read' => false,
                    'update' => true,
                ],
            ]);

            expect($wrapper->getOrganizerTypes())->toBe(['create', 'update']);
        });

        it('returns empty array when organizers is false or missing', function () {
            $wrapper1 = new ModelConfigWrapper('User', ['organizers' => false]);
            $wrapper2 = new ModelConfigWrapper('User', []);

            expect($wrapper1->getOrganizerTypes())->toBe([])
                ->and($wrapper2->getOrganizerTypes())->toBe([]);
        });
    });

    describe('getOrganizerConfig', function () {
        it('returns an OrganizerConfig instance', function () {
            $wrapper = new ModelConfigWrapper('User', ['organizers' => true]);
            expect($wrapper->getOrganizerConfig())->toBeInstanceOf(\Flowlight\Generator\Config\OrganizerConfig::class);
        });
    });
});
