<?php

namespace Tests\Unit\Generators;

use Blueprint\Tree;
use Flowlight\Generator\Config\ModelConfigWrapper;
use Flowlight\Generator\Generators\DtoGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->generator = new DtoGenerator($this->filesystem);
});

afterEach(function () {
    Mockery::close();
});

describe('DtoGenerator', function () {
    describe('types', function () {
        it('returns correct types', function () {
            expect($this->generator->types())->toBe(['api']);
        });
    });

    describe('output', function () {
        it('returns empty array when no models exist', function () {
            $tree = Mockery::mock(Tree::class);
            $tree->shouldReceive('models')->andReturn([]);

            $output = $this->generator->output($tree);

            expect($output)->toBeEmpty();
        });

        it('returns empty array when no models should generate DTO', function () {
            $tree = Mockery::mock(Tree::class);
            $tree->shouldReceive('models')->andReturn([
                'User' => ['dto' => false],
            ]);

            // Mock filesystem calls safely (even if never triggered)
            $this->filesystem->shouldReceive('get')
                ->withArgs(fn ($path) => str_ends_with($path, 'dto.stub'))
                ->andReturn('stub content');
            $this->filesystem->shouldReceive('ensureDirectoryExists');
            $this->filesystem->shouldReceive('put');

            $output = $this->generator->output($tree);

            expect($output)->toEqual([]);
        });

        it('generates DTO for models that should generate DTO', function () {
            $tree = Mockery::mock(Tree::class);
            $tree->shouldReceive('models')->andReturn([
                'User' => [
                    'dto' => [
                        'namespace' => 'App\\Domain\\Users\\Data',
                        'extends' => 'Flowlight\\BaseData',
                    ],
                    'fields' => [
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                    ],
                ],
            ]);

            $this->filesystem->shouldReceive('get')
                ->withArgs(fn ($path) => str_ends_with($path, 'dto.stub'))
                ->andReturn('stub content');
            $this->filesystem->shouldReceive('ensureDirectoryExists');
            $this->filesystem->shouldReceive('put');

            $output = $this->generator->output($tree);

            expect($output)->toHaveKey('User');
            expect($output['User'])->toBeString();
        });

        it('skips models without DTO config', function () {
            $tree = Mockery::mock(Tree::class);
            $tree->shouldReceive('models')->andReturn([
                'User' => [
                    'fields' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ]);

            $output = $this->generator->output($tree);

            expect($output)->toEqual([]);
        });
    });

    describe('generateDto', function () {
        it('generates DTO file with correct content', function () {
            $wrapper = Mockery::mock(ModelConfigWrapper::class);
            $dtoConfig = Mockery::mock(\Flowlight\Generator\Config\DtoConfig::class);

            $wrapper->shouldReceive('shouldGenerateDto')->andReturn(true);
            $wrapper->shouldReceive('getDtoConfig')->andReturn($dtoConfig);
            $wrapper->shouldReceive('getFields')->andReturn(collect());

            $dtoConfig->shouldReceive('getNamespace')->andReturn('App\\Domain\\Users\\Data');
            $dtoConfig->shouldReceive('getClassName')->andReturn('UserData');
            $dtoConfig->shouldReceive('getExtends')->andReturn('Flowlight\\BaseData');
            $dtoConfig->shouldReceive('getCustomMessages')->andReturn([]);

            // 👇 Loosen expectation
            $this->filesystem->shouldReceive('get')
                ->withArgs(fn ($path) => str_ends_with($path, 'dto.stub'))
                ->andReturn('namespace {{ namespace }}; class {{ class }} extends {{ extends }} { {{ attributes }} {{ messages }} {{ rules }} }');

            $this->filesystem->shouldReceive('ensureDirectoryExists');
            $this->filesystem->shouldReceive('put')->andReturnUsing(function ($path, $content) {
                expect($content)->toContain('namespace App\\Domain\\Users\\Data');
                expect($content)->toContain('class UserData');
                expect($content)->toContain('extends Flowlight\\BaseData');
            });

            $result = $this->generator->generateDto($wrapper);

            expect($result)->toBeString();
        });
    });

    describe('generateAttributes', function () {
        it('generates attributes array from fields', function () {
            $wrapper = Mockery::mock(ModelConfigWrapper::class);
            $field1 = Mockery::mock(\Flowlight\Generator\Config\FieldConfig::class);
            $field2 = Mockery::mock(\Flowlight\Generator\Config\FieldConfig::class);

            $field1->shouldReceive('getName')->andReturn('name');
            $field1->shouldReceive('getAttributeLabel')->andReturn('Name');

            $field2->shouldReceive('getName')->andReturn('email');
            $field2->shouldReceive('getAttributeLabel')->andReturn('Email');

            $wrapper->shouldReceive('getFields')->andReturn(collect([$field1, $field2]));

            $result = $this->generator->generateAttributes($wrapper);

            expect($result)->toContain("'name' => 'Name'");
            expect($result)->toContain("'email' => 'Email'");
        });

        it('returns empty string when no fields', function () {
            $wrapper = Mockery::mock(ModelConfigWrapper::class);
            $wrapper->shouldReceive('getFields')->andReturn(collect());

            $result = $this->generator->generateAttributes($wrapper);

            expect($result)->toBeEmpty();
        });
    });

    describe('generateMessages', function () {
        it('generates messages array from field messages and custom messages', function () {
            $wrapper = Mockery::mock(ModelConfigWrapper::class);
            $field = Mockery::mock(\Flowlight\Generator\Config\FieldConfig::class);
            $dtoConfig = Mockery::mock(\Flowlight\Generator\Config\DtoConfig::class);

            $field->shouldReceive('getName')->andReturn('email');
            $field->shouldReceive('getMessages')->andReturn([
                'required' => 'Email is required',
                'email' => 'Must be a valid email',
            ]);

            $wrapper->shouldReceive('getFields')->andReturn(collect([$field]));
            $wrapper->shouldReceive('getDtoConfig')->andReturn($dtoConfig);
            $dtoConfig->shouldReceive('getCustomMessages')->andReturn([
                'custom.rule' => 'Custom message',
            ]);

            $result = $this->generator->generateMessages($wrapper);

            expect($result)->toContain("'email.required' => 'Email is required'");
            expect($result)->toContain("'email.email' => 'Must be a valid email'");
            expect($result)->toContain("'custom.rule' => 'Custom message'");
        });

        it('returns empty string when no messages', function () {
            $wrapper = Mockery::mock(ModelConfigWrapper::class);
            $dtoConfig = Mockery::mock(\Flowlight\Generator\Config\DtoConfig::class);

            $wrapper->shouldReceive('getFields')->andReturn(collect());
            $wrapper->shouldReceive('getDtoConfig')->andReturn($dtoConfig);
            $dtoConfig->shouldReceive('getCustomMessages')->andReturn([]);

            $result = $this->generator->generateMessages($wrapper);

            expect($result)->toBeEmpty();
        });
    });

    describe('generateRules', function () {
        it('generates rules array from field rules', function () {
            $wrapper = Mockery::mock(ModelConfigWrapper::class);
            $field1 = Mockery::mock(\Flowlight\Generator\Config\FieldConfig::class);
            $field2 = Mockery::mock(\Flowlight\Generator\Config\FieldConfig::class);

            $field1->shouldReceive('getName')->andReturn('name');
            $field1->shouldReceive('getRules')->andReturn(['required', 'string', 'max:255']);

            $field2->shouldReceive('getName')->andReturn('email');
            $field2->shouldReceive('getRules')->andReturn(['required', 'email']);

            $wrapper->shouldReceive('getFields')->andReturn(collect([$field1, $field2]));

            $result = $this->generator->generateRules($wrapper);

            expect($result)->toContain("'name' => [");
            expect($result)->toContain("'required'");
            expect($result)->toContain("'string'");
            expect($result)->toContain("'max:255'");
            expect($result)->toContain("'email' => [");
            expect($result)->toContain("'required'");
            expect($result)->toContain("'email'");
        });

        it('returns empty string when no fields', function () {
            $wrapper = Mockery::mock(ModelConfigWrapper::class);
            $wrapper->shouldReceive('getFields')->andReturn(collect());

            $result = $this->generator->generateRules($wrapper);

            expect($result)->toBeEmpty();
        });
    });

    describe('getPath', function () {
        it('converts namespace to file path correctly', function () {
            $result = $this->generator->getPath('App\\Domain\\Users\\Data', 'UserData');

            expect($result)->toContain('app/Domain/Users/Data/UserData.php');
            expect($result)->toEndWith('.php');
        });

        it('handles nested namespaces correctly', function () {
            $result = $this->generator->getPath('App\\Domain\\Very\\Nested\\Namespace', 'TestDto');

            expect($result)->toContain('app/Domain/Very/Nested/Namespace/TestDto.php');
        });

        it('uses base path for App namespace', function () {
            $result = $this->generator->getPath('App\\Models', 'User');

            expect($result)->toContain('app/Models/User.php');
        });
    });
});
