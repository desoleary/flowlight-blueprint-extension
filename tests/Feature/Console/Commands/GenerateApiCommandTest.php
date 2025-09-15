<?php

namespace Tests\Feature\Console\Commands;

use Flowlight\Generator\Console\Commands\GenerateApiCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Tester\CommandTester;

it('generates files locally with valid input', function () {
    $tempDir = sys_get_temp_dir().'/flowlight_test_'.uniqid();
    mkdir($tempDir, 0777, true);

    // Swap blueprint config to point into temp dir
    config()->set('blueprint.app_path', $tempDir);
    config()->set('blueprint.models_namespace', 'App\\Models');

    // Boot a fresh console app + command
    $consoleApp = new ConsoleApplication;
    $command = new GenerateApiCommand;
    $command->setLaravel(app());
    $consoleApp->add($command);

    $tester = new CommandTester($consoleApp->find('flowlight:generate'));

    $exitCode = $tester->execute([
        'entity' => 'User',
        '--fields' => 'name:string email:string?',
    ]);

    expect($exitCode)->toBe(0);
    expect($tester->getDisplay())->toContain('Generated:');

    $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempDir));
    $files = [];
    foreach ($rii as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    expect($files)->not->toBeEmpty();

    // Cleanup
    exec('rm -rf '.escapeshellarg($tempDir));
});
