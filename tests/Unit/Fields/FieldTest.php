<?php

declare(strict_types=1);

use Flowlight\Generator\Fields\Field;

describe('getName', function () {
    it('returns the provided name', function () {
        $f = new Field('email', 'string');
        expect($f->getName())->toBe('email');
    });
});

describe('getType', function () {
    it('returns configured type', function () {
        $f = new Field('age', ['type' => 'integer']);
        expect($f->getType())->toBe('integer');
    });

    it('defaults to string when not provided', function () {
        $f = new Field('bio', []);
        expect($f->getType())->toBe('string');
    });
});

describe('isRequired', function () {
    it('defaults to true', function () {
        $f = new Field('title', []);
        expect($f->isRequired())->toBeTrue();
    });

    it('respects explicit false', function () {
        $f = new Field('nickname', ['required' => false]);
        expect($f->isRequired())->toBeFalse();
    });
});

describe('getLength', function () {
    it('returns configured length', function () {
        $f = new Field('bio', ['type' => 'string', 'length' => 255]);
        expect($f->getLength())->toBe(255);
    });

    it('returns null when not set', function () {
        $f = new Field('email', 'string');
        expect($f->getLength())->toBeNull();
    });
});

describe('getAttributeLabel', function () {
    it('returns explicit attribute label', function () {
        $f = new Field('age', ['attribute' => 'Years Old']);
        expect($f->getAttributeLabel())->toBe('Years Old');
    });

    it('generates a title-cased label from snake_case', function () {
        $f = new Field('first_name', 'string');
        expect($f->getAttributeLabel())->toBe('First Name');
    });
});

describe('getRules', function () {
    it('uses explicit rules and does not add required when nullable present', function () {
        $f = new Field('name', [
            'rules' => ['nullable', 'string'],
            'required' => true,
            'length' => 32,
        ]);
        expect($f->getRules())->toEqual(['nullable', 'string', 'max:32']);
    });

    it('merges default type rules when none provided and adds required', function () {
        $f = new Field('email', ['type' => 'email', 'required' => true]);
        expect($f->getRules())->toEqual(['required', 'string', 'email']);
    });

    it('adds sometimes for optional fields and length for string/text only', function () {
        $f = new Field('title', ['type' => 'string', 'required' => false, 'length' => 12]);
        expect($f->getRules())->toBe(['sometimes', 'string', 'max:12']);
    });

    it('does not add max:length for non-string types', function () {
        $f = new Field('count', ['type' => 'integer', 'length' => 10]);
        expect($f->getRules())->toEqual(['required', 'integer']);
    });

    it('does not duplicate sometimes if already present', function () {
        $f = new Field('nickname', ['type' => 'string', 'rules' => ['sometimes']]);
        expect($f->getRules())->toEqual(['sometimes', 'string']);
    });

    it('deduplicates duplicate explicit rules and keeps a single required at front', function () {
        $f = new Field('title', ['type' => 'string', 'rules' => ['required', 'required', 'string']]);
        expect($f->getRules())->toBe(['required', 'string']);
    });

    it('places nullable at the front when present', function () {
        $f = new Field('name', [
            'type' => 'string',
            'rules' => ['string', 'nullable'],
        ]);

        expect($f->getRules()[0])->toBe('nullable');
    });

    it('places sometimes at the front when present', function () {
        $f = new Field('nickname', [
            'type' => 'string',
            'rules' => ['string', 'sometimes'],
        ]);

        expect($f->getRules()[0])->toBe('sometimes');
    });

    it('moves nullable to the front if present', function () {
        $f = new Field('name', [
            'type' => 'string',
            'rules' => ['string', 'nullable'],
            'required' => true,
        ]);

        $rules = $f->getRules();

        expect($rules[0])->toBe('nullable');
        expect($rules)->toContain('string');
    });

    it('moves sometimes to the front if present', function () {
        $f = new Field('nickname', [
            'type' => 'string',
            'rules' => ['string', 'sometimes'],
            'required' => true,
        ]);

        $rules = $f->getRules();

        expect($rules[0])->toBe('sometimes');
        expect($rules)->toContain('string');
    });

    it('keeps required first when neither nullable nor sometimes present', function () {
        $f = new Field('title', [
            'type' => 'string',
            'rules' => ['string'],
            'required' => true,
        ]);

        $rules = $f->getRules();

        expect($rules[0])->toBe('required');
    });

    it('places both nullable and sometimes at the front, order preserved by input', function () {
        $f = new Field('nickname', [
            'type' => 'string',
            'rules' => ['sometimes', 'nullable', 'string'],
        ]);

        $rules = $f->getRules();

        // both should be at the start
        expect($rules[0])->toBeIn(['nullable', 'sometimes']);
        expect($rules[1])->toBeIn(['nullable', 'sometimes']);
        expect($rules)->toContain('string');
    });
});

describe('getMessages', function () {
    it('generates default messages for all known rules and a sensible default for unknown', function () {
        $f = new Field('first_name', ['type' => 'string', 'rules' => ['email', 'integer']]);
        $messages = $f->getMessages();
        expect($messages)->toHaveKey('first_name.required')
            ->and($messages['first_name.string'])->toBe('First Name must be text.')
            ->and($messages['first_name.email'])->toBe('First Name must be a valid email address.')
            ->and($messages['first_name.integer'])->toBe('First Name must be an integer.');
    });

    it('respects user-provided message overrides', function () {
        $f = new Field('email', [
            'rules' => ['required', 'max:255', 'email'],
            'messages' => [
                'required' => 'Custom required.',
                'max' => 'No more than :max characters.',
                'email' => 'Please provide a valid email address.',
            ],
        ]);
        $messages = $f->getMessages();
        expect($messages['email.required'])->toBe('Custom required.')
            ->and($messages['email.max'])->toBe('No more than :max characters.')
            ->and($messages['email.email'])->toBe('Please provide a valid email address.');
    });

    it('auto-generates messages for rules added by getRules', function () {
        $f = new Field('display_name', ['type' => 'string', 'required' => false, 'length' => 12]);
        $rules = $f->getRules();
        expect($rules)->toBe(['sometimes', 'string', 'max:12']);
        $messages = $f->getMessages();
        expect($messages)->toHaveKeys(['display_name.sometimes', 'display_name.string', 'display_name.max']);
    });

    it('keeps message key as-is when dot present', function () {
        $f = new Field('email', [
            'rules' => ['required'],
            'messages' => [
                'email.required' => 'Already namespaced message',
            ],
        ]);

        $messages = $f->getMessages();

        expect($messages)->toHaveKey('email.required');
        expect($messages['email.required'])->toBe('Already namespaced message');
    });
});

describe('getPhpType', function () {
    it('returns correct php types', function () {
        expect((new Field('name', 'string'))->getPhpType())->toBe('string')
            ->and((new Field('age', ['type' => 'integer']))->getPhpType())->toBe('int')
            ->and((new Field('bio', ['type' => 'string', 'required' => false]))->getPhpType())->toBe('?string')
            ->and((new Field('when', ['type' => 'date']))->getPhpType())->toBe('\DateTimeInterface');
    });
});

describe('toArray', function () {
    it('returns enriched array', function () {
        $f = new Field('custom_field', [
            'type' => 'string',
            'required' => false,
            'length' => 42,
            'attribute' => 'Custom Label',
            'messages' => ['max' => 'Too long.'],
        ]);
        $array = $f->toArray();
        expect($array)->toHaveKey('name', 'custom_field')
            ->and($array)->toHaveKey('phpType', '?string')
            ->and($array)->toHaveKey('attributes')
            ->and($array['attributes'])->toHaveKey('custom_field', 'Custom Label');
    });
});

describe('toRendererArray', function () {
    it('exports renderer array for mustache templates', function () {
        $field = new Field('age', ['type' => 'integer', 'required' => false]);
        $renderer = $field->toRendererArray();
        expect($renderer['name'])->toBe('age')
            ->and($renderer['phpType'])->toBe('?int')
            ->and($renderer['attribute'])->toBe('Age')
            ->and($renderer['rules'])->toBeArray()
            ->and($renderer['messages'])->toBeArray();
    });
});

describe('__construct (string shorthand)', function () {
    it('normalizes shorthand string config into type array', function () {
        $f = new Field('email', 'string');
        expect($f->getType())->toBe('string');
    });
});

describe('normalizeMessageOrder', function () {
    it('sorts unknown rules alphabetically when both indexes false', function () {
        $f = new Field('code', [
            'rules' => ['foo', 'bar'],
            'messages' => [
                'code.foo' => 'Foo msg',
                'code.bar' => 'Bar msg',
            ],
        ]);

        $messages = $f->getMessages();

        $keys = array_keys($messages);
        expect($keys)->toEqual(['code.required', 'code.string', 'code.bar', 'code.foo']);
    });

    it('places unknown rule after known rule when aIndex false', function () {
        $f = new Field('value', [
            'rules' => ['required', 'custom'],
            'messages' => [
                'value.required' => 'Req msg',
                'value.custom' => 'Custom msg',
            ],
        ]);

        $keys = array_keys($f->getMessages());

        expect($keys[0])->toBe('value.required');
        expect($keys)->toContain('value.custom');
    });

    it('places unknown rule before known rule when bIndex false', function () {
        $f = new Field('value', [
            'rules' => ['custom', 'required'],
            'messages' => [
                'value.required' => 'Req msg',
                'value.custom' => 'Custom msg',
            ],
        ]);

        $keys = array_keys($f->getMessages());

        expect($keys)->toContain('value.required');
        expect($keys)->toContain('value.custom');
    });
});
