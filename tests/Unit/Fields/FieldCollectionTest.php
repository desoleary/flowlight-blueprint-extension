<?php

use Flowlight\Generator\Fields\FieldCollection;

describe('__construct', function () {
    it('constructs from raw field config arrays', function () {
        $collection = new FieldCollection([
            'name' => ['type' => 'string', 'required' => true],
            'age' => ['type' => 'integer', 'required' => false],
        ]);

        expect($collection->count())->toBe(2);
        expect($collection->get('name')->getType())->toBe('string');
        expect($collection->get('age')->getType())->toBe('integer');
    });

    it('constructs with mixed fields still as raw arrays only', function () {
        $collection = new FieldCollection([
            'title' => ['type' => 'string'],
            'active' => ['type' => 'boolean'],
        ]);

        expect($collection->count())->toBe(2);
        expect($collection->get('active')->getType())->toBe('boolean');
        expect($collection->get('title')->getType())->toBe('string');
    });
});

describe('required', function () {
    it('filters required fields', function () {
        $collection = new FieldCollection([
            'name' => ['type' => 'string', 'required' => true],
            'age' => ['type' => 'integer', 'required' => false],
        ]);

        $required = $collection->required();
        expect($required->count())->toBe(1);
        expect($required->keys()->toArray())->toContain('name');
    });
});

describe('optional', function () {
    it('filters optional fields', function () {
        $collection = new FieldCollection([
            'name' => ['type' => 'string', 'required' => true],
            'nickname' => ['type' => 'string', 'required' => false],
        ]);

        $optional = $collection->optional();
        expect($optional->count())->toBe(1);
        expect($optional->keys()->toArray())->toContain('nickname');
    });
});

describe('ofType', function () {
    it('filters fields by type', function () {
        $collection = new FieldCollection([
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
            'email' => ['type' => 'string'],
        ]);

        $strings = $collection->ofType('string');
        expect($strings->count())->toBe(2);
        expect($strings->keys()->toArray())->toContain('name', 'email');
    });

    it('returns empty collection when no fields match', function () {
        $collection = new FieldCollection([
            'age' => ['type' => 'integer'],
        ]);

        $strings = $collection->ofType('string');
        expect($strings->count())->toBe(0);
        expect($strings->all())->toBeArray()->toBeEmpty();
    });
});

describe('__construct (edge case)', function () {
    it('handles empty initialization gracefully', function () {
        $collection = new FieldCollection([]);

        expect($collection->count())->toBe(0);
        expect($collection->all()->all())->toBeArray()->toBeEmpty();
    });
});

describe('getIterator', function () {
    it('allows foreach iteration over fields', function () {
        $collection = new FieldCollection([
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
        ]);

        $names = [];
        $types = [];

        foreach ($collection as $name => $field) {
            $names[] = $name;
            $types[] = $field->getType();
        }

        expect($names)->toBe(['name', 'age']);
        expect($types)->toBe(['string', 'integer']);
    });
});

describe('keys', function () {
    it('returns collection of field names', function () {
        $collection = new FieldCollection([
            'title' => ['type' => 'string'],
            'active' => ['type' => 'boolean'],
        ]);

        $keys = $collection->keys()->toArray();

        expect($keys)->toBe(['title', 'active']);
    });

    it('returns empty collection when no fields exist', function () {
        $collection = new FieldCollection([]);

        $keys = $collection->keys()->toArray();

        expect($keys)->toBe([]);
    });
});
