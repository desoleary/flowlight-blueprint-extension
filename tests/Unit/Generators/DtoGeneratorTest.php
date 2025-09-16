<?php

namespace Tests\Unit\Generators;

use Flowlight\Generator\Config\DtoConfig;
use Flowlight\Generator\Generators\DtoGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery;

beforeEach(function () {
    $this->files = Mockery::mock(Filesystem::class);

    $this->generator = Mockery::mock(DtoGenerator::class, [$this->files])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // Ensure safe file operations
    $this->files->shouldReceive('makeDirectory')->andReturnTrue();
    $this->files->shouldReceive('put')->andReturnTrue();

    // Force consistent path for generated file
    $this->generator->shouldReceive('getPath')
        ->andReturn(sys_get_temp_dir().'/flowlight_test_app/UserData.php');
});

afterEach(function () {
    Mockery::close();
    exec('rm -rf '.escapeshellarg(sys_get_temp_dir().'/flowlight_test_app'));
});

describe('DtoGenerator', function () {
    describe('types', function () {
        it('returns correct types', function () {
            expect($this->generator->types())->toBe(['api']);
        });
    });

    describe('output', function () {
        it('returns empty created list when dto not defined', function () {
            $model = new DtoConfig('User', [
                'fields' => ['name' => ['type' => 'string']],
            ], 'dto');

            $output = $this->generator->output($model, 'class {{ class }} {}');

            expect($output)->toBe(['created' => []]);
        });

        it('returns empty created list when dto is false', function () {
            $model = new DtoConfig('User', [
                'fields' => ['name' => ['type' => 'string']],
                'dto' => false,
            ], 'dto');

            $output = $this->generator->output($model, 'class {{ class }} {}');

            expect($output)->toBe(['created' => []]);
        });

        it('generates dto file when dto flag is true', function () {
            $stub = <<<'PHP'
<?php

namespace {{ namespace }};

class {{ class }}
{
}
PHP;

            $model = new DtoConfig('User', [
                'fields' => ['name' => ['type' => 'string']],
                'dto' => true,
            ], 'dto');

            $output = $this->generator->output($model, $stub);

            expect($output['created'])->not->toBeEmpty();
            expect($output['created'][0])->toEndWith('UserData.php');
        });

        it('generates dto file when dto has config array', function () {
            $stub = 'class {{ class }} {}';

            $model = new DtoConfig('User', [
                'fields' => ['name' => ['type' => 'string']],
                'dto' => ['namespace' => 'Custom\\NS'],
            ], 'dto');

            $output = $this->generator->output($model, $stub);

            expect($output['created'])->not->toBeEmpty();
            expect($output['created'][0])->toEndWith('UserData.php');
        });
    });
});
