<?php

namespace Tests\Unit\Generators;

use Flowlight\Generator\Config\ModelConfigWrapper;
use Flowlight\Generator\Generators\PluggableGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery;

beforeEach(function () {
    // Fake subclass that implements populateStub
    $this->generator = new class(Mockery::mock(Filesystem::class), ['key' => 'dto', 'namespace' => 'App\\Domain\\{{modelName}}s\\Data', 'suffix' => 'Data']) extends PluggableGenerator
    {
        public function populateStub(
            string $stub,
            string $modelName,
            string $namespace,
            string $className,
            ?string $extends,
            ModelConfigWrapper $model
        ): string {
            return str_replace(
                ['{{ modelName }}', '{{ namespace }}', '{{ class }}', '{{ extends }}'],
                [$modelName, $namespace, $className, $extends ?? ''],
                $stub
            );
        }
    };

    // Fake wrapper with minimal implementation
    $this->wrapper = new class('User', ['dto' => true, 'fields' => ['name' => ['type' => 'string']]], 'dto') extends ModelConfigWrapper
    {
        protected function getDefaultNamespace(): string
        {
            return 'App\\Domain\\Users\\Data';
        }

        protected function getDefaultClassName(): string
        {
            return 'UserData';
        }

        protected function getDefaultExtendedClassName(): ?string
        {
            return 'BaseDto';
        }
    };

    // Filesystem mock
    $this->files = Mockery::mock(Filesystem::class);
});

afterEach(function () {
    Mockery::close();
});

describe('PluggableGenerator', function () {
    it('returns api type', function () {
        expect($this->generator->types())->toBe(['api']);
    });

    it('skips output when shouldGenerate is false', function () {
        $wrapper = new class('User', [], 'dto') extends ModelConfigWrapper
        {
            protected function getDefaultNamespace(): string
            {
                return 'NS';
            }

            protected function getDefaultClassName(): string
            {
                return 'X';
            }

            protected function getDefaultExtendedClassName(): ?string
            {
                return 'Base';
            }
        };

        $output = $this->generator->output($wrapper, 'class {{ class }} {}');
        expect($output)->toBe(['created' => []]);
    });

    it('renders and writes a file when generation is enabled', function () {
        $stub = <<<'PHP'
<?php

namespace {{ namespace }};

class {{ class }} {{ extends }}
{
}
PHP;

        $path = sys_get_temp_dir().'/flowlight_test/UserData.php';

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('makeDirectory')->andReturnTrue();
        $files->shouldReceive('put')
            ->once()
            ->with($path, Mockery::on(fn ($content) => str_contains($content, 'class UserData')));

        $gen = new class($files, ['key' => 'dto', 'namespace' => 'App\\Domain\\{{modelName}}s\\Data', 'suffix' => 'Data']) extends PluggableGenerator
        {
            public function populateStub(
                string $stub,
                string $modelName,
                string $namespace,
                string $className,
                ?string $extends,
                ModelConfigWrapper $model
            ): string {
                return str_replace(
                    ['{{ modelName }}', '{{ namespace }}', '{{ class }}', '{{ extends }}'],
                    [$modelName, $namespace, $className, $extends ?? ''],
                    $stub
                );
            }

            protected function getPath(string $namespace, string $className): string
            {
                return sys_get_temp_dir().'/flowlight_test/'.$className.'.php';
            }
        };

        $output = $gen->output($this->wrapper, $stub);

        expect($output['created'][0])->toEndWith('UserData.php');
    });

    it('throws when blueprint.app_path is not a string', function () {
        config(['blueprint.app_path' => ['invalid']]);

        $gen = $this->generator;

        expect(fn () => $gen->output($this->wrapper, ''))->toThrow(\RuntimeException::class);
    });
});
