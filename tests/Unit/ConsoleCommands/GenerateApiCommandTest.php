<?php

use Flowlight\Generator\Console\Commands\GenerateApiCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    $this->consoleApp = new ConsoleApplication;
    $this->command = new GenerateApiCommand;

    // Inject our test container
    $this->command->setLaravel(app());

    $this->consoleApp->add($this->command);
});

describe('parseFields', function () {
    it('parses simple required string field', function () {
        $command = new class extends GenerateApiCommand
        {
            public function callParse(string $fields): string
            {
                return $this->parseFields($fields);
            }
        };

        $yaml = $command->callParse('name:string');

        expect($yaml)->toContain('name:')
            ->toContain('type: string')
            ->toContain('required: true');
    });

    it('parses optional string field', function () {
        $command = new class extends GenerateApiCommand
        {
            public function callParse(string $fields): string
            {
                return $this->parseFields($fields);
            }
        };

        $yaml = $command->callParse('email:string?');

        expect($yaml)->toContain('email:')
            ->toContain('type: string')
            ->not->toContain('required: true');
    });

    it('parses decimal with length and precision', function () {
        $command = new class extends GenerateApiCommand
        {
            public function callParse(string $fields): string
            {
                return $this->parseFields($fields);
            }
        };

        $yaml = $command->callParse('amount:decimal:10:2');

        expect($yaml)->toContain('amount:')
            ->toContain('type: decimal')
            ->toContain('length: 10')
            ->toContain('precision: 2')
            ->toContain('required: true');
    });
});

describe('buildYamlConfig', function () {
    it('wraps parsed fields into a valid yaml structure', function () {
        $command = new class extends GenerateApiCommand
        {
            public function callBuild(string $entity, string $fields): string
            {
                return $this->buildYamlConfig($entity, $fields);
            }
        };

        $yaml = $command->callBuild('User', 'name:string email:string?');

        expect($yaml)->toContain('api:')
            ->toContain('User:')
            ->toContain('dto:')
            ->toContain('organizers: true')
            ->toContain('namespace: App\\Domain\\Users\\Data');
    });
});

describe('handle', function () {
    it('fails when entity is not string', function () {
        $tester = new CommandTester($this->consoleApp->find('flowlight:generate'));

        $tester->execute(['entity' => 123]);

        expect($tester->getStatusCode())->toBe(Command::FAILURE)
            ->and($tester->getDisplay())->toContain('Entity must be a string');
    });

    it('fails when fields option is not string', function () {
        $tester = new CommandTester($this->consoleApp->find('flowlight:generate'));

        $tester->execute(['entity' => 'User', '--fields' => ['bad']]);

        expect($tester->getStatusCode())->toBe(Command::FAILURE)
            ->and($tester->getDisplay())->toContain('Fields must be a string');
    });

    it('handles null fields option by setting it to empty string', function () {
        // Mock Blueprint and Tree
        $blueprint = Mockery::mock(\Blueprint\Blueprint::class);
        $tree = Mockery::mock(\Blueprint\Tree::class);

        $blueprint->shouldReceive('parse')->andReturn($tree);
        $blueprint->shouldReceive('generate')->andReturn([
            'User.php' => '<?php // generated',
        ]);

        // Bind the mock blueprint to the Laravel container
        app()->instance(\Blueprint\Blueprint::class, $blueprint);

        // Create command tester
        $tester = new CommandTester($this->consoleApp->find('flowlight:generate'));

        // Execute without providing --fields option (which will be null)
        $tester->execute([
            'entity' => 'User',
            // Don't include --fields option to test null case
        ]);

        expect($tester->getStatusCode())->toBe(Command::SUCCESS);
        expect($tester->getDisplay())->toContain('Generated:');

        Mockery::close();
    });

    it('runs successfully with valid inputs', function () {
        // Mock Blueprint and Tree
        $blueprint = Mockery::mock(\Blueprint\Blueprint::class);
        $tree = Mockery::mock(\Blueprint\Tree::class);

        $blueprint->shouldReceive('parse')->andReturn($tree);
        $blueprint->shouldReceive('generate')->andReturn([
            'User.php' => '<?php // generated',
            'UserData.php' => '<?php // generated data',
            'UserOrganizer.php' => '<?php // generated organizer',
        ]);

        // Bind the mock blueprint to the Laravel container
        app()->instance(\Blueprint\Blueprint::class, $blueprint);

        // Create command tester
        $tester = new CommandTester($this->consoleApp->find('flowlight:generate'));

        // Execute with valid inputs
        $tester->execute([
            'entity' => 'User',
            '--fields' => 'name:string email:string?',
        ]);

        expect($tester->getStatusCode())->toBe(Command::SUCCESS);
        expect($tester->getDisplay())->toContain('Generated:');

        Mockery::close();
    });
});
