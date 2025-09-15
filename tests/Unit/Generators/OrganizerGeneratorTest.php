<?php

namespace Tests\Unit\Generators;

use Blueprint\Tree;
use Flowlight\Generator\Generators\OrganizerGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery;

beforeEach(function () {
    $this->files = Mockery::mock(Filesystem::class);

    $this->generator = Mockery::mock(OrganizerGenerator::class, [$this->files])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // Ensure safe file operations
    $this->files->shouldReceive('makeDirectory')->andReturnTrue();
    $this->files->shouldReceive('put')->andReturnTrue();

    // Force consistent path for generated file
    $this->generator->shouldReceive('getPath')
        ->andReturn(sys_get_temp_dir().'/flowlight_test_app/UserOrganizer.php');
});

afterEach(function () {
    Mockery::close();
    exec('rm -rf '.escapeshellarg(sys_get_temp_dir().'/flowlight_test_app'));
});

describe('OrganizerGenerator', function () {
    describe('types', function () {
        it('returns correct types', function () {
            expect($this->generator->types())->toBe(['api']);
        });
    });

    describe('output', function () {
        it('returns empty created list when no api definitions exist', function () {
            $tree = new Tree([]);

            $output = $this->generator->output($tree, 'class {{ class }} {}');

            expect($output)->toBe(['created' => []]);
        });

        it('skips entities without organizers flag', function () {
            $tree = new Tree([
                'api' => [
                    'User' => [
                        'fields' => ['name' => ['type' => 'string']],
                    ],
                ],
            ]);

            $output = $this->generator->output($tree, 'class {{ class }} {}');

            expect($output)->toBe(['created' => []]);
        });

        it('generates organizer file when organizers flag is true', function () {
            $stub = <<<'PHP'
<?php

namespace {{ namespace }};

class {{ class }}
{
}
PHP;

            $tree = new Tree([
                'api' => [
                    'User' => [
                        'fields' => ['name' => ['type' => 'string']],
                        'organizers' => true,
                    ],
                ],
            ]);

            $output = $this->generator->output($tree, $stub);

            expect($output['created'])->not->toBeEmpty();
            expect($output['created'][0])->toEndWith('UserOrganizer.php');
        });
    });
});
