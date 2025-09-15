<?php

namespace Tests\Unit\Generators;

use Blueprint\Tree;
use Flowlight\Generator\Generators\DtoGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery;

beforeEach(function () {
    $this->files = Mockery::mock(Filesystem::class);
    $this->generator = Mockery::mock(DtoGenerator::class, [$this->files])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $this->generator->shouldAllowMockingProtectedMethods();
    $this->generator->shouldReceive('getPath')->andReturn(
        sys_get_temp_dir().'/flowlight_test_app/UserData.php'
    );
});

afterEach(function () {
    exec('rm -rf '.escapeshellarg(sys_get_temp_dir().'/flowlight_test_app'));

    Mockery::close();
});

describe('DtoGenerator', function () {
    it('returns correct types', function () {
        expect($this->generator->types())->toBe(['api']);
    });

    it('builds properties for required and optional fields', function () {
        $fields = [
            'id' => ['type' => 'int', 'required' => true],
            'name' => ['type' => 'string'],
            'tags' => ['type' => 'array', 'required' => false],
            'meta' => 'invalid', // should be skipped
        ];

        $result = $this->generator->buildProperties($fields);

        expect($result)->toContain('@var int')
            ->toContain('public $id;')
            ->toContain('@var ?string')
            ->toContain('public $name;')
            ->toContain('@var ?array')
            ->toContain('public $tags;')
            ->not->toContain('meta');
    });

    it('populates stub with namespace, class, extends and properties', function () {
        $stub = <<<'PHP'
<?php

namespace {{ namespace }};

class {{ class }} {{ extends }}
{
{{ properties }}
}
PHP;

        $definition = [
            'dto' => ['extends' => 'BaseDto'],
            'fields' => [
                'email' => ['type' => 'string', 'required' => true],
            ],
        ];

        $result = $this->generator->populateStub(
            $stub,
            'User',
            'App\\Domain\\Users\\Data',
            'UserData',
            $definition
        );

        expect($result)->toContain('namespace App\\Domain\\Users\\Data;')
            ->toContain('class UserData extends BaseDto')
            ->toContain('@var string')
            ->toContain('public $email;');
    });

    it('populates stub without extends or properties when missing', function () {
        $stub = 'namespace {{ namespace }}; class {{ class }} {{ extends }} { {{ properties }} }';

        $definition = []; // no dto, no fields

        $result = $this->generator->populateStub(
            $stub,
            'User',
            'App\\Domain\\Users\\Data',
            'UserData',
            $definition
        );

        expect($result)->toContain('namespace App\\Domain\\Users\\Data;')
            ->toContain('class UserData')
            ->not->toContain('extends')
            ->not->toContain('public $');
    });

    it('outputs generated file paths from Tree', function () {
        $stub = 'namespace {{ namespace }}; class {{ class }} {{ extends }} { {{ properties }} }';

        $tree = new Tree([
            'api' => [
                'User' => [
                    'dto' => ['extends' => 'BaseDto'],
                    'fields' => [
                        'id' => ['type' => 'int', 'required' => true],
                    ],
                ],
            ],
        ]);

        // Expect filesystem interactions
        $this->files->shouldReceive('makeDirectory')->andReturnTrue();
        $this->files->shouldReceive('put')
            ->withArgs(function ($path, $contents) {
                expect($path)->toEndWith('UserData.php');
                expect($contents)->toContain('class UserData extends BaseDto');

                return true;
            })
            ->once();

        $output = $this->generator->output($tree, $stub);

        expect($output['created'][0])->toEndWith('UserData.php');
    });

    it('skips entities without dto config', function () {
        $tree = new Tree([
            'api' => [
                'Post' => [
                    'fields' => ['title' => ['type' => 'string']],
                ],
            ],
        ]);

        $output = $this->generator->output($tree, 'stub');

        expect($output)->toBe(['created' => []]);
    });
});
