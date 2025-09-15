<?php

namespace Flowlight\Generator\Console\Commands;

use Blueprint\Blueprint;
use Blueprint\Tree;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Artisan command for generating API components (DTOs and Organizers).
 *
 * This command leverages Blueprint and Flowlight conventions to
 * scaffold API-related artifacts for a given entity. It supports
 * parsing field definitions and building a temporary YAML
 * configuration that Blueprint can consume.
 *
 * Usage:
 *  php artisan flowlight:generate User --fields="name:string email:string? age:int"
 *
 * Options:
 * - entity    (argument, required): Name of the entity/model (e.g., "User").
 * - --fields  (string, optional) : Space-delimited list of field definitions.
 *                                  Format: `name:type[:length[:precision]][?]`
 *                                  Example: `amount:decimal:10:2?`
 * - --dto     (flag)              : Whether to generate DTOs.
 * - --organizers (flag)           : Whether to generate Organizers.
 */
class GenerateApiCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'flowlight:generate {entity} {--fields=} {--dto} {--organizers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API components including DTOs and Organizers';

    /**
     * Execute the console command.
     *
     * @param  \Blueprint\Blueprint  $blueprint  The Blueprint instance for parsing and generating.
     * @return int Command::SUCCESS on success, Command::FAILURE on error.
     */
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

    /**
     * Build the YAML configuration string for Blueprint.
     *
     * This method assembles a temporary YAML config block
     * based on the entity name and parsed fields. The config
     * instructs Blueprint to generate the API, DTO, and
     * organizers for the entity.
     *
     * @param  string  $entity  The entity name (e.g., "User").
     * @param  string  $fields  Space-delimited field definitions string.
     * @return string YAML configuration string.
     */
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

    /**
     * Parse the `--fields` string into YAML field definitions.
     *
     * Expected format for each field:
     *   name:type[:length[:precision]][?]
     *
     * Examples:
     * - "name:string" → required string field.
     * - "email:string?" → optional string field.
     * - "amount:decimal:10:2" → required decimal(10,2) field.
     *
     * @param  string  $fields  The raw fields string.
     * @return string YAML fragment representing the fields.
     */
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
