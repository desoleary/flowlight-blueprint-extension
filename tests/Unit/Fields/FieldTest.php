<?php

use Flowlight\Generator\Fields\Field;

beforeEach(function () {
    config()->set('flowlight.field_types', [
        'string' => ['default_rules' => ['string']],
        'text' => ['default_rules' => ['string']],
        'email' => ['default_rules' => ['string', 'email']],
        'integer' => ['default_rules' => ['integer']],
        'numeric' => ['default_rules' => ['numeric']],
        'boolean' => ['default_rules' => ['boolean']],
        'date' => ['default_rules' => ['date']],
    ]);
});

describe('getName', function () {
    it('returns the provided name', function () {
        $f = new Field('first_name', ['type' => 'string']);
        expect($f->getName())->toBe('first_name');
    });
});

describe('getType', function () {
    it('returns configured type', function () {
        $f = new Field('email', ['type' => 'email']);
        expect($f->getType())->toBe('email');
    });

    it('defaults to string when not provided', function () {
        $f = new Field('anything', []);
        expect($f->getType())->toBe('string');
    });
});

describe('isRequired', function () {
    it('defaults to true', function () {
        $f = new Field('x', []);
        expect($f->isRequired())->toBeTrue();
    });

    it('respects explicit false', function () {
        $f = new Field('x', ['required' => false]);
        expect($f->isRequired())->toBeFalse();
    });
});

describe('getLength', function () {
    it('returns configured length', function () {
        $f = new Field('title', ['length' => 64]);
        expect($f->getLength())->toBe(64);
    });

    it('returns null when not set', function () {
        $f = new Field('title', []);
        expect($f->getLength())->toBeNull();
    });
});

describe('getAttributeLabel', function () {
    it('returns explicit attribute label', function () {
        $f = new Field('email', ['attribute' => 'Email Address']);
        expect($f->getAttributeLabel())->toBe('Email Address');
    });

    it('generates a title-cased label from snake_case', function () {
        $f = new Field('first_name', []);
        expect($f->getAttributeLabel())->toBe('First Name');
    });
});

describe('getRules', function () {
    it('uses explicit rules and does not add required when nullable present', function () {
        $f = new Field('name', [
            'type' => 'string',
            'length' => 32,
            'rules' => ['nullable', 'string'],
            'required' => true,
        ]);

        expect($f->getRules())->toBe(['nullable', 'string', 'max:32']);
    });

    it('merges default type rules when none provided and adds required', function () {
        $f = new Field('email', [
            'type' => 'email',
            'required' => true,
        ]);

        expect($f->getRules())->toBe(['required', 'string', 'email']);
    });

    it('adds sometimes for optional fields and length for string/text only', function () {
        $f = new Field('title', [
            'type' => 'string',
            'length' => 10,
            'required' => false,
        ]);

        expect($f->getRules())->toBe(['sometimes', 'string', 'max:10']);
    });

    it('does not add max:length for non-string types', function () {
        $f = new Field('age', [
            'type' => 'integer',
            'length' => 10,
            'required' => true,
        ]);

        expect($f->getRules())->toBe(['required', 'integer']);
    });

    it('does not duplicate sometimes if already present', function () {
        $f = new Field('nickname', [
            'type' => 'string',
            'rules' => ['sometimes', 'string'],
            'required' => false,
        ]);

        expect($f->getRules())->toBe(['sometimes', 'string']);
    });

    it('deduplicates duplicate explicit rules and keeps a single required at front', function () {
        $f = new Field('email', [
            'type' => 'email',
            'rules' => ['required', 'required', 'email', 'email'],
            'required' => true,
        ]);

        expect($f->getRules())->toBe(['required', 'email']);
    });
});

describe('getMessages', function () {
    it('generates default messages for all known rules and a sensible default for unknown', function () {
        $f = new Field('first_name', [
            'attribute' => 'First Name',
            'rules' => [
                'required',
                'string',
                'email',
                'integer',
                'numeric',
                'boolean',
                'date',
                'max:5',
                'min:2',
                'in:red,green,blue',
                'uuid',
            ],
        ]);

        $messages = $f->getMessages();

        expect($messages)->toMatchArray([
            'required' => 'First Name is required.',
            'string' => 'First Name must be text.',
            'email' => 'Please provide a valid email address.',
            'integer' => 'First Name must be an integer.',
            'numeric' => 'First Name must be a number.',
            'boolean' => 'First Name must be true or false.',
            'date' => 'First Name must be a valid date.',
            'max' => 'First Name cannot exceed 5.',
            'min' => 'First Name must be at least 2.',
            'in' => 'First Name must be one of: red,green,blue.',
            'uuid' => 'Validation failed for First Name.',
        ]);
    });

    it('respects user-provided message overrides', function () {
        $f = new Field('email', [
            'attribute' => 'Email',
            'rules' => ['required', 'max:10', 'email'],
            'messages' => [
                'required' => 'Custom required.',
                'max' => 'No more than :max characters.', // note: class won’t interpolate :max; we’re asserting override
            ],
        ]);

        $messages = $f->getMessages();

        expect($messages['required'])->toBe('Custom required.');
        expect($messages['max'])->toBe('No more than :max characters.');
        expect($messages['email'])->toBe('Please provide a valid email address.');
    });

    it('auto-generates messages for rules added by getRules (e.g., required/sometimes, max:length)', function () {
        $f = new Field('display_name', [
            'type' => 'string',
            'length' => 12,
            'required' => false,
            'rules' => ['string'],
        ]);

        $rules = $f->getRules();
        expect($rules)->toBe(['sometimes', 'string', 'max:12']);

        $messages = $f->getMessages();

        expect($messages)->toHaveKeys(['sometimes', 'string', 'max']);
        expect($messages['max'])->toBe('Display Name cannot exceed 12.');
        expect($messages['string'])->toBe('Display Name must be text.');
        expect($messages['sometimes'])->toBe('Validation failed for Display Name.');
    });
});

describe('toArray', function () {
    it('returns the raw config array', function () {
        $config = [
            'type' => 'string',
            'required' => false,
            'length' => 42,
            'attribute' => 'Custom Label',
            'rules' => ['string', 'max:42'],
            'messages' => ['max' => 'Too long.'],
        ];

        $f = new Field('custom_field', $config);

        expect($f->toArray())->toBe($config);
    });
});

describe('__construct (string shorthand)', function () {
    it('normalizes shorthand string config into type array', function () {
        $f = new Field('status', 'boolean');

        expect($f->getType())->toBe('boolean');
        expect($f->isRequired())->toBeTrue(); // defaults to true
        expect($f->getAttributeLabel())->toBe('Status');
    });
});
