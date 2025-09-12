<?php

namespace Flowlight\Generator\Console\Commands;

use Blueprint\Blueprint;
use Blueprint\Tree;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class GenerateApiCommand extends Command
{
    protected $signature = 'flowlight:generate {entity} {--fields=} {--dto} {--organizers}';

    protected $description = 'Generate API components including DTOs and Organizers';

    public function handle(Blueprint $blueprint): int
    {
        $entity = $this->argument('entity');
        if (! is_string($entity)) {
            $this->error('Entity must be a string.');

            return Command::FAILURE;
        }

        $fields = $this->option('fields');
        if ($fields === null) {
            $fields = '';
        } elseif (! is_string($fields)) {
            $this->error('Fields must be a string.');

            return Command::FAILURE;
        }

        $yamlConfig = $this->buildYamlConfig($entity, $fields);

        /** @var array<string,mixed> $parsed */
        $parsed = Yaml::parse($yamlConfig);

        /** @var Tree $tree */
        $tree = $blueprint->parse($parsed);

        $generated = $blueprint->generate($tree);

        foreach ($generated as $file => $content) {
            $this->info("Generated: {$file}");
        }

        return Command::SUCCESS;
    }

    protected function buildYamlConfig(string $entity, string $fields): string
    {
        $fieldDefinitions = $this->parseFields($fields);

        return <<<YAML
api:
  {$entity}:
    fields:
{$fieldDefinitions}
    dto:
      namespace: App\\Domain\\{$entity}s\\Data
      extends: Flowlight\\BaseData
    organizers: true
YAML;
    }

    protected function parseFields(string $fields): string
    {
        $parsed = [];
        $parts = explode(' ', $fields);

        foreach ($parts as $part) {
            if (preg_match('/^(\w+):([a-z]+)(?::(\d+))?(?::(\d+))?(\?)?$/', $part, $matches)) {
                $fieldName = $matches[1];
                $fieldType = $matches[2];
                $length = $matches[3] ?? null;
                $precision = $matches[4] ?? null;
                $nullable = isset($matches[5]);

                $fieldConfig = "      {$fieldName}:\n        type: {$fieldType}";

                if ($length) {
                    $fieldConfig .= "\n        length: {$length}";
                }

                if ($precision) {
                    $fieldConfig .= "\n        precision: {$precision}";
                }

                if (! $nullable) {
                    $fieldConfig .= "\n        required: true";
                }

                $parsed[] = $fieldConfig;
            }
        }

        return implode("\n", $parsed);
    }
}
