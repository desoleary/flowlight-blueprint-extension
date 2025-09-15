<?php

namespace Tests\Unit\Generators;

use Blueprint\Tree;
use Flowlight\Generator\Generators\OrganizerGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery;

beforeEach(function () {
    $this->filesystem = Mockery::mock(Filesystem::class);
    $this->generator = new OrganizerGenerator($this->filesystem);
});

afterEach(function () {
    Mockery::close();
});

describe('OrganizerGenerator', function () {
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

        it('returns empty array when no models should generate organizers', function () {
            $tree = Mockery::mock(Tree::class);
            $tree->shouldReceive('models')->andReturn([
                'User' => ['organizers' => false],
            ]);

            $output = $this->generator->output($tree);

            expect($output)->toEqual([]);
        });

        it('generates organizer info for models that should generate organizers', function () {
            $tree = Mockery::mock(Tree::class);
            $tree->shouldReceive('models')->andReturn([
                'User' => ['organizers' => true],
            ]);

            $output = $this->generator->output($tree);

            expect($output)->toHaveKey('User');
            expect($output['User'])->toContain('Organizers for User would be generated here.');
        });

        it('skips models without organizers config', function () {
            $tree = Mockery::mock(Tree::class);
            $tree->shouldReceive('models')->andReturn([
                'User' => ['fields' => ['name' => ['type' => 'string']]],
            ]);

            $output = $this->generator->output($tree);

            expect($output)->toEqual([]);
        });
    });
});
