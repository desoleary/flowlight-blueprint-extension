<?php

namespace Tests\Unit\Generators;

use Flowlight\Generator\Config\OrganizerConfig;
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
        it('returns empty created list when organizers not defined', function () {
            $model = new OrganizerConfig('User', [
                'fields' => ['name' => ['type' => 'string']],
            ], 'organizers');

            $output = $this->generator->output($model, 'class {{ class }} {}');

            expect($output)->toBe(['created' => []]);
        });

        it('returns empty created list when organizers is false', function () {
            $model = new OrganizerConfig('User', [
                'fields' => ['name' => ['type' => 'string']],
                'organizers' => false,
            ], 'organizers');

            $output = $this->generator->output($model, 'class {{ class }} {}');

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

            $model = new OrganizerConfig('User', [
                'fields' => ['name' => ['type' => 'string']],
                'organizers' => true,
            ], 'organizers');

            $output = $this->generator->output($model, $stub);

            expect($output['created'])->not->toBeEmpty();
            expect($output['created'][0])->toEndWith('UserOrganizer.php');
        });
    });
});
