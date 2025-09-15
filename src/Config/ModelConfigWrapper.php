<?php

namespace Flowlight\Generator\Config;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Model configuration wrapper for Flowlight code generation.
 *
 * Encapsulates configuration for a model, including:
 * - Table name
 * - Fields and their rules/messages
 * - DTO configuration
 * - Organizer configuration
 *
 * Provides accessors and defaults, so consuming code can easily
 * work with a normalized representation of the model configuration.
 *
 * @phpstan-type FieldConfigArray array{
 *     type?: string,
 *     required?: bool,
 *     length?: int,
 *     attribute?: string,
 *     rules?: list<string>,
 *     messages?: array<string,string>
 * }
 * @phpstan-type ModelConfigArray array{
 *     table?: string,
 *     fields?: array<string, FieldConfigArray>,
 *     dto?: array<string,mixed>|true,
 *     organizers?: array<string,bool>|bool
 * }
 */
class ModelConfigWrapper
{
    /**
     * Raw model configuration.
     *
     * @var ModelConfigArray
     */
    protected array $config;

    /**
     * The name of the model.
     *
     * Used to derive default table names, DTOs, and organizers.
     */
    protected string $modelName;

    /**
     * Collection of field configurations for this model.
     *
     * Keys are field names, values are {@see FieldConfig} instances.
     *
     * @var Collection<string, FieldConfig>
     */
    protected Collection $fields;

    /**
     * Create a new model configuration wrapper.
     *
     * @param  string  $modelName  The name of the model.
     * @param  ModelConfigArray  $config  The raw configuration array.
     */
    public function __construct(string $modelName, array $config)
    {
        $this->modelName = $modelName;
        $this->config = $config;
        $this->fields = $this->initializeFields();
    }

    /**
     * Initialize field configuration objects from the raw config.
     *
     * @return Collection<string, FieldConfig> Collection of field configs.
     */
    protected function initializeFields(): Collection
    {
        $fields = collect();

        foreach ($this->config['fields'] ?? [] as $fieldName => $fieldConfig) {
            /** @var FieldConfigArray $fieldConfig */
            $fields->put($fieldName, new FieldConfig($fieldName, $fieldConfig));
        }

        return $fields;
    }

    /**
     * Get the model name.
     *
     * @return string The model name.
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * Get the table name for the model.
     *
     * - Defaults to the snake-cased, pluralized model name.
     * - Can be overridden via the `table` key in config.
     *
     * @return string The table name.
     */
    public function getTableName(): string
    {
        return $this->config['table'] ?? Str::snake(Str::plural($this->modelName));
    }

    /**
     * Determine if a DTO should be generated for the model.
     *
     * @return bool True if DTO generation is enabled or fields exist.
     */
    public function shouldGenerateDto(): bool
    {
        // Generate DTO if explicitly enabled
        if (($this->config['dto'] ?? null) === true) {
            return true;
        }

        // Generate DTO if dto is a non-empty array
        if (is_array($this->config['dto'] ?? null) && ! empty($this->config['dto'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the DTO configuration wrapper for this model.
     *
     * @return DtoConfig DTO configuration object.
     */
    public function getDtoConfig(): DtoConfig
    {
        /** @var array<string,mixed>|true $dtoConfig */
        $dtoConfig = $this->config['dto'] ?? [];

        return new DtoConfig($dtoConfig, $this->modelName);
    }

    /**
     * Get all field configurations.
     *
     * @return Collection<string, FieldConfig> Collection of field configs.
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * Get a specific field configuration by name.
     *
     * @param  string  $fieldName  The field name.
     * @return FieldConfig|null The field config or null if not found.
     */
    public function getField(string $fieldName): ?FieldConfig
    {
        return $this->fields->get($fieldName);
    }

    /**
     * Determine if organizers should be generated for this model.
     *
     * @return bool True if organizers config exists and is not false.
     */
    public function shouldGenerateOrganizers(): bool
    {
        return isset($this->config['organizers']) && $this->config['organizers'] !== false;
    }

    /**
     * Get the list of organizer types to generate.
     *
     * - Defaults to `['create','read','update','delete','list']` when `organizers` is `true`.
     * - Otherwise, returns only the enabled organizer keys.
     *
     * @return list<string> List of organizer operation types.
     */
    public function getOrganizerTypes(): array
    {
        if (! $this->shouldGenerateOrganizers()) {
            return [];
        }

        $organizers = $this->config['organizers'] ?? [];

        if ($organizers === true) {
            return ['create', 'read', 'update', 'delete', 'list'];
        }

        // Ensure keys are cast to string for PHPStan
        return array_map(
            static fn ($key): string => (string) $key,
            array_keys(array_filter((array) $organizers))
        );
    }

    /**
     * Get the organizer configuration wrapper for this model.
     *
     * @return OrganizerConfig Organizer configuration object.
     */
    public function getOrganizerConfig(): OrganizerConfig
    {
        /** @var array<string,bool>|true|false $organizers */
        $organizers = $this->config['organizers'] ?? [];

        return new OrganizerConfig($organizers, $this->modelName);
    }
}
