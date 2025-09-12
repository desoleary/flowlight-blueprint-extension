<?php

namespace Flowlight\Generator\Config;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
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
 *     fields?: array<string, array{type?: string, required?: bool, length?: int, attribute?: string, rules?: list<string>, messages?: array<string,string>}>,
 *     dto?: array<string,mixed>|true,
 *     organizers?: array<string,bool>|bool
 * }
 */
class ModelConfigWrapper
{
    /** @var ModelConfigArray */
    protected array $config;

    protected string $modelName;

    /** @var Collection<string, FieldConfig> */
    protected Collection $fields;

    /**
     * @param  ModelConfigArray  $config
     */
    public function __construct(string $modelName, array $config)
    {
        $this->modelName = $modelName;
        $this->config = $config;
        $this->fields = $this->initializeFields();
    }

    /**
     * @return Collection<string, FieldConfig>
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

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getTableName(): string
    {
        return $this->config['table'] ?? Str::snake(Str::plural($this->modelName));
    }

    public function shouldGenerateDto(): bool
    {
        return isset($this->config['dto']) || $this->fields->isNotEmpty();
    }

    public function getDtoConfig(): DtoConfig
    {
        /** @var array<string,mixed>|true $dtoConfig */
        $dtoConfig = $this->config['dto'] ?? [];

        return new DtoConfig($dtoConfig, $this->modelName);
    }

    /**
     * @return Collection<string, FieldConfig>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getField(string $fieldName): ?FieldConfig
    {
        return $this->fields->get($fieldName);
    }

    public function shouldGenerateOrganizers(): bool
    {
        return isset($this->config['organizers']) && $this->config['organizers'] !== false;
    }

    /**
     * @return list<string>
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

    public function getOrganizerConfig(): OrganizerConfig
    {
        /** @var array<string,bool>|true|false $organizers */
        $organizers = $this->config['organizers'] ?? [];

        return new OrganizerConfig($organizers, $this->modelName);
    }
}
