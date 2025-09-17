<?php

use Blueprint\Tree;
use Flowlight\Generator\Config\ModelConfigWrapper;
use Flowlight\Generator\Generators\ApiGenerator;
use Flowlight\Generator\Generators\PluggableGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;

function makeAnonWrapperClass(): string
{
    $obj = new class('User', [], 'dto') extends ModelConfigWrapper
    {
        protected function getDefaultNamespace(): string
        {
            return 'App\\Domain\\Tests';
        }

        protected function getDefaultClassName(): string
        {
            return 'TestData';
        }

        protected function getDefaultExtendedClassName(): ?string
        {
            return null;
        }
    };

    return get_class($obj);
}

function makeAnonGeneratorClass(Filesystem $files): string
{
    $obj = new class($files, ['key' => 'dto', 'namespace' => 'App\\Generated', 'suffix' => 'Gen']) extends PluggableGenerator
    {
        public function __construct(Filesystem $files, array $pluginConfig)
        {
            parent::__construct($files, array_merge([
                'key' => 'dto',
                'namespace' => 'App\\Generated',
                'suffix' => 'Gen',
            ], $pluginConfig));
        }

        public function populateStub(string $stub, string $modelName, string $namespace, string $className, ?string $extends, ModelConfigWrapper $model): string
        {
            return "// {$className}";
        }

        public function output(ModelConfigWrapper $model, string $stub): array
        {
            return ['created' => ['/tmp/'.$model->getModelName().'.php']];
        }
    };

    return get_class($obj);
}

function makeApiGenWithConfig(Filesystem $files, array $cfg): ApiGenerator
{
    $GLOBALS['__apigen_cfg'] = $cfg;

    return new class($files) extends ApiGenerator
    {
        protected function getConfig(): array
        {
            return $GLOBALS['__apigen_cfg'] ?? [];
        }
    };
}

function makeApiGenProbe(Filesystem $files, array $configured): ApiGenerator
{
    $GLOBALS['__apigen_probe_cfg'] = $configured;

    return new class($files) extends ApiGenerator
    {
        public function __construct(Filesystem $files)
        {
            $this->files = $files;
            $this->configured = $GLOBALS['__apigen_probe_cfg'] ?? [];
        }

        protected function getConfig(): array
        {
            return $GLOBALS['__apigen_probe_cfg'] ?? [];
        }

        public function callResolveStub(string $key): string
        {
            return parent::resolveStub($key);
        }
    };
}

function makeApiGenRealProbe(Filesystem $files): ApiGenerator
{
    return new class($files) extends ApiGenerator
    {
        public function __construct(Filesystem $files)
        {
            parent::__construct($files);
        }

        public function callResolveStub(string $key): string
        {
            return parent::resolveStub($key);
        }
    };
}

beforeEach(function () {
    $this->files = m::mock(Filesystem::class);
});

describe('output', function () {
    it('generates output with configured generator', function () {
        $genClass = makeAnonGeneratorClass($this->files);
        $cfgClass = makeAnonWrapperClass();

        $gen = makeApiGenWithConfig($this->files, [
            'dto' => ['class' => $genClass, 'stub' => 'dto.stub.hbs', 'config_class' => $cfgClass],
        ]);

        $this->files->shouldReceive('exists')->withArgs(fn ($p) => str_contains($p, 'stubs/flowlight/dto.stub.hbs'))->andReturn(false);
        $this->files->shouldReceive('exists')->withArgs(fn ($p) => str_contains($p, '/stubs/dto.stub.hbs'))->andReturn(true);
        $this->files->shouldReceive('get')->withArgs(fn ($p) => str_contains($p, '/stubs/dto.stub.hbs'))->andReturn('// default dto stub');

        $tree = m::mock(Tree::class);
        $tree->shouldReceive('toArray')->andReturn(['api' => ['User' => ['dto' => true]]]);

        $output = $gen->output($tree);

        expect($output)->toHaveKey('created');
        expect($output['created'])->toContain('/tmp/User.php');
    });
});

describe('requireConfigValue', function () {
    it('throws when class key is missing', function () {
        $cfgClass = makeAnonWrapperClass();
        $expect = fn () => makeApiGenWithConfig($this->files, [
            'dto' => ['stub' => 'dto.stub.hbs', 'config_class' => $cfgClass],
        ]);
        expect($expect)->toThrow(LogicException::class);
    });
    it('throws when stub key is missing', function () {
        $genClass = makeAnonGeneratorClass($this->files);
        $cfgClass = makeAnonWrapperClass();
        $expect = fn () => makeApiGenWithConfig($this->files, [
            'dto' => ['class' => $genClass, 'config_class' => $cfgClass],
        ]);
        expect($expect)->toThrow(LogicException::class);
    });
    it('throws when config_class key is missing', function () {
        $genClass = makeAnonGeneratorClass($this->files);
        $expect = fn () => makeApiGenWithConfig($this->files, [
            'dto' => ['class' => $genClass, 'stub' => 'dto.stub.hbs'],
        ]);
        expect($expect)->toThrow(LogicException::class);
    });
});

describe('resolveStub', function () {
    it('throws when stubFile is empty string', function () {
        $probe = makeApiGenProbe($this->files, ['dto' => ['stub' => '']]);
        $expect = fn () => $probe->callResolveStub('dto');
        expect($expect)->toThrow(RuntimeException::class);
    });
    it('throws when stubFile is not a string', function () {
        $probe = makeApiGenProbe($this->files, ['dto' => ['stub' => null]]);
        $expect = fn () => $probe->callResolveStub('dto');
        expect($expect)->toThrow(RuntimeException::class);
    });
    it('returns the default stub when custom override does not exist', function () {
        $probe = makeApiGenProbe($this->files, ['dto' => ['stub' => 'dto.stub.hbs']]);
        $this->files->shouldReceive('exists')->once()->withArgs(fn ($p) => str_contains($p, 'stubs/flowlight/dto.stub.hbs'))->andReturn(false);
        $this->files->shouldReceive('exists')->once()->withArgs(fn ($p) => str_contains($p, '/stubs/dto.stub.hbs'))->andReturn(true);
        $this->files->shouldReceive('get')->once()->withArgs(fn ($p) => str_contains($p, '/stubs/dto.stub.hbs'))->andReturn('// default dto stub');
        $stub = $probe->callResolveStub('dto');
        expect($stub)->toBe('// default dto stub');
    });
    it('returns custom stub contents when custom override exists (real getConfig)', function () {
        $genClass = makeAnonGeneratorClass($this->files);
        $cfgClass = makeAnonWrapperClass();
        config()->set('flowlight.generators', [
            'dto' => ['class' => $genClass, 'stub' => 'dto.stub.hbs', 'config_class' => $cfgClass],
        ]);
        $gen = makeApiGenRealProbe($this->files);
        $this->files->shouldReceive('exists')->once()->withArgs(fn ($p) => str_contains($p, 'stubs/flowlight/dto.stub.hbs'))->andReturn(true);
        $this->files->shouldReceive('get')->once()->withArgs(fn ($p) => str_contains($p, 'stubs/flowlight/dto.stub.hbs'))->andReturn('// custom dto stub');
        $stub = $gen->callResolveStub('dto');
        expect($stub)->toBe('// custom dto stub');
    });
    it('returns default stub contents when custom is missing and default exists (real getConfig)', function () {
        $genClass = makeAnonGeneratorClass($this->files);
        $cfgClass = makeAnonWrapperClass();
        config()->set('flowlight.generators', [
            'dto' => ['class' => $genClass, 'stub' => 'dto.stub.hbs', 'config_class' => $cfgClass],
        ]);
        $gen = makeApiGenRealProbe($this->files);
        $this->files->shouldReceive('exists')->once()->withArgs(fn ($p) => str_contains($p, 'stubs/flowlight/dto.stub.hbs'))->andReturn(false);
        $this->files->shouldReceive('exists')->once()->withArgs(fn ($p) => str_contains($p, '/stubs/dto.stub.hbs'))->andReturn(true);
        $this->files->shouldReceive('get')->once()->withArgs(fn ($p) => str_contains($p, '/stubs/dto.stub.hbs'))->andReturn('// default dto stub');
        $stub = $gen->callResolveStub('dto');
        expect($stub)->toBe('// default dto stub');
    });
    it('throws when neither custom nor default stub exists (real getConfig)', function () {
        $genClass = makeAnonGeneratorClass($this->files);
        $cfgClass = makeAnonWrapperClass();
        config()->set('flowlight.generators', [
            'dto' => ['class' => $genClass, 'stub' => 'dto.stub.hbs', 'config_class' => $cfgClass],
        ]);
        $gen = makeApiGenRealProbe($this->files);
        $this->files->shouldReceive('exists')->once()->withArgs(fn ($p) => str_contains($p, 'stubs/flowlight/dto.stub.hbs'))->andReturn(false);
        $this->files->shouldReceive('exists')->once()->withArgs(fn ($p) => str_contains($p, '/stubs/dto.stub.hbs'))->andReturn(false);
        $expect = fn () => $gen->callResolveStub('dto');
        expect($expect)->toThrow(RuntimeException::class);
    });
});

describe('types', function () {
    it('returns ["api"]', function () {
        $genClass = makeAnonGeneratorClass($this->files);
        $cfgClass = makeAnonWrapperClass();
        $gen = makeApiGenWithConfig($this->files, [
            'dto' => ['class' => $genClass, 'stub' => 'dto.stub.hbs', 'config_class' => $cfgClass],
        ]);
        expect($gen->types())->toBe(['api']);
    });
});
