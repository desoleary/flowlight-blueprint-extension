<?php

use Flowlight\Generator\Config\DtoConfig;
use Flowlight\Generator\Generators\DtoGenerator;
use Tests\Support\InMemoryFilesystem;

beforeEach(function () {
    $this->files = new InMemoryFilesystem;
    $this->generator = new DtoGenerator($this->files);
});

describe('DtoGenerator full variations', function () {
    it('generates DTO with required and optional primitives', function () {
        $stub = file_get_contents(__DIR__.'/../../../stubs/dto.stub.hbs');

        $model = new DtoConfig('User', [
            'fields' => [
                'name' => ['type' => 'string', 'required' => true],
                'email' => ['type' => 'string?', 'required' => false, 'length' => 255],
                'age' => ['type' => 'int', 'required' => false],
                'balance' => ['type' => 'float'],
                'is_active' => ['type' => 'bool'],
            ],
            'dto' => true,
        ], 'dto');

        $output = $this->generator->output($model, $stub);

        expect($output['created'])->not->toBeEmpty();

        $contents = $this->files->get($output['created'][0]);
        expect($contents)->toMatchSnapshot();
    });

    it('respects custom labels and messages', function () {
        $stub = file_get_contents(__DIR__.'/../../../stubs/dto.stub.hbs');

        $model = new DtoConfig('Product', [
            'fields' => [
                'title' => [
                    'type' => 'string',
                    'attribute' => 'Product Title',
                    'messages' => [
                        'required' => 'You must provide a title!',
                    ],
                ],
                'status' => [
                    'type' => 'string',
                    'rules' => ['in:draft,published'],
                ],
            ],
            'dto' => true,
        ], 'dto');

        $output = $this->generator->output($model, $stub);

        $contents = $this->files->get($output['created'][0]);
        expect($contents)->toMatchSnapshot();
    });

    it('supports date/time fields', function () {
        $stub = file_get_contents(__DIR__.'/../../../stubs/dto.stub.hbs');

        $model = new DtoConfig('Event', [
            'fields' => [
                'starts_at' => ['type' => 'date'],
                'ends_at' => ['type' => 'datetime', 'required' => false],
            ],
            'dto' => true,
        ], 'dto');

        $output = $this->generator->output($model, $stub);

        $contents = $this->files->get($output['created'][0]);
        expect($contents)->toMatchSnapshot();
    });

    it('supports enums with validation', function () {
        $stub = file_get_contents(__DIR__.'/../../../stubs/dto.stub.hbs');

        $model = new DtoConfig('Order', [
            'fields' => [
                'status' => [
                    'type' => 'string',
                    'rules' => ['in:pending,paid,shipped'],
                ],
            ],
            'dto' => true,
        ], 'dto');

        $output = $this->generator->output($model, $stub);

        $contents = $this->files->get($output['created'][0]);
        expect($contents)->toMatchSnapshot();
    });

    it('supports nested arrays/collections', function () {
        $stub = file_get_contents(__DIR__.'/../../../stubs/dto.stub.hbs');

        $model = new DtoConfig('Invoice', [
            'fields' => [
                'line_items' => [
                    'type' => 'array',
                    'rules' => ['array'],
                ],
            ],
            'dto' => true,
        ], 'dto');

        $output = $this->generator->output($model, $stub);

        $contents = $this->files->get($output['created'][0]);
        expect($contents)->toMatchSnapshot();
    });

    it('applies custom messages overrides', function () {
        $stub = file_get_contents(__DIR__.'/../../../stubs/dto.stub.hbs');

        $model = new DtoConfig('Customer', [
            'fields' => [
                'email' => [
                    'type' => 'string',
                    'messages' => [
                        'required' => 'Email is absolutely required!',
                        'string' => 'Email must be plain text only.',
                    ],
                ],
                'age' => [
                    'type' => 'int',
                    'required' => false,
                    'messages' => [
                        'integer' => 'Age must be numeric only.',
                    ],
                ],
            ],
            'dto' => true,
        ], 'dto');

        $output = $this->generator->output($model, $stub);

        $contents = $this->files->get($output['created'][0]);

        expect($contents)->toMatchSnapshot();
    });

    it('merges custom and default messages', function () {
        $stub = file_get_contents(__DIR__.'/../../../stubs/dto.stub.hbs');

        $model = new DtoConfig('Account', [
            'fields' => [
                'username' => [
                    'type' => 'string',
                    'messages' => [
                        'required' => 'Username cannot be blank!',
                    ],
                ],
            ],
            'dto' => true,
        ], 'dto');

        $output = $this->generator->output($model, $stub);

        $contents = $this->files->get($output['created'][0]);

        expect($contents)->toMatchSnapshot();
    });
});
