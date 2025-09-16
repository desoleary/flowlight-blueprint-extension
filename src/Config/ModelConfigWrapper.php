<?php

namespace Flowlight\Generator\Config;

use Flowlight\Generator\Fields\Field;
use Flowlight\Generator\Fields\FieldCollection;
use Illuminate\Support\Collection;

/**
 * Generic configuration wrapper for a single API model definition.
 *
 * Wraps the raw definition for a model from Blueprint’s parsed tree
 * (e.g., from draft.yml under `api`). Provides normalized accessors
 * for namespace, className, extended class, fields, etc.
 *
 * Children must implement fallback logic for their type-specific defaults
 * by defining:
 * - getDefaultNamespace()
 * - getDefaultClassName()
 * - getDefaultExtendedClassName()
 *
 * @phpstan-type FieldConfigArray array{
 *     type?: string,
 *     required?: bool,
 *     length?: int,
 *     attribute?: string,
 *     rules?: list<string>,
 *     messages?: array<string,string>
 * }
 * @phpstan-type ModelDefinition array{
 *     table?: string,
 *     fields?: array<string, FieldConfigArray>,
 *     dto?: array<string,mixed>|true,
 *     organizers?: array<string,bool>|bool
 * }
 */
abstract class ModelConfigWrapper
{
    /**
     * The raw model definition array from Blueprint.
     *
     * @var ModelDefinition
     */
    protected array $definition;

    /**
     * The model name (e.g., "User").
     */
    protected string $modelName;

    /**
     * The generator type this wrapper is bound to (e.g., "dto", "organizers").
     */
    protected string $type;

    /**
     * Normalized collection of field configs.
     *
     * Keys are field names, values are {@see Field}.
     */
    protected FieldCollection $fields;

    /**
     * @param  string  $modelName  Model name (e.g., "User").
     * @param  ModelDefinition  $definition  Raw model definition from Tree.
     * @param  string  $type  Target generator type ("dto", "organizers", etc.).
     */
    public function __construct(string $modelName, array $definition, string $type)
    {
        $this->modelName = $modelName;
        $this->definition = $definition;
        $this->type = $type;
        $this->fields = $this->initializeFields();
    }

    /**
     * Fallback namespace if not defined in config.
     */
    abstract protected function getDefaultNamespace(): string;

    /**
     * Fallback class name if not defined in config.
     */
    abstract protected function getDefaultClassName(): string;

    /**
     * Fallback parent class if not defined in config.
     *
     * Return null if there is no natural base class.
     */
    abstract protected function getDefaultExtendedClassName(): ?string;

    /**
     * Get the model name.
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * Get the generator type (e.g., "dto", "organizers").
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Raw definition array for this model.
     *
     * @return ModelDefinition
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * Get all field configs.
     */
    public function getFields(): FieldCollection
    {
        return $this->fields;
    }

    /**
     * Get a single field by name.
     */
    public function getField(string $name): ?Field
    {
        return $this->fields->get($name);
    }

    /**
     * Does this model enable generation for the current type?
     */
    public function shouldGenerate(): bool
    {
        $section = $this->definition[$this->type] ?? null;

        if ($section === true) {
            return true;
        }

        return is_array($section) && ! empty($section);
    }

    /**
     * Namespace for the generated class.
     *
     * - Uses `namespace` from definition if present.
     * - Otherwise falls back to {@see getDefaultNamespace()}.
     */
    public function getNamespace(): string
    {
        $section = $this->definition[$this->type] ?? [];

        if (is_array($section) && isset($section['namespace']) && is_string($section['namespace'])) {
            return $section['namespace'];
        }

        $fallback = $this->getDefaultNamespace();
        $this->throwIfEmptyValue($fallback, 'No default namespace provided');

        return $fallback;
    }

    /**
     * Class name for the generated type.
     *
     * - Uses `className` from definition if present.
     * - Otherwise falls back to {@see getDefaultClassName()}.
     */
    public function getClassName(): string
    {
        $section = $this->definition[$this->type] ?? [];

        if (is_array($section) && isset($section['className']) && is_string($section['className'])) {
            return $section['className'];
        }

        $fallback = $this->getDefaultClassName();
        $this->throwIfEmptyValue($fallback, 'No default class name provided');

        return $fallback;
    }

    /**
     * Parent class to extend, if configured.
     *
     * - Uses `extends` from definition if present.
     * - Otherwise falls back to {@see getDefaultExtendedClassName()}.
     */
    public function getExtendedClassName(): ?string
    {
        $section = $this->definition[$this->type] ?? [];

        if (is_array($section) && isset($section['extends']) && is_string($section['extends'])) {
            return $section['extends'];
        }

        $fallback = $this->getDefaultExtendedClassName();
        $this->throwIfEmptyValue($fallback, 'No default extended class name provided');

        return $fallback;
    }

    /**
     * Table name (default: snake plural of model name).
     */
    public function getTableName(): string
    {
        if (! isset($this->definition['table'])) {
            throw new \LogicException(static::class.': table name must be provided');
        }

        $tableName = (string) $this->definition['table'];
        $this->throwIfEmptyValue($tableName, 'table name must be provided');

        return $tableName;
    }

    /**
     * Initialize field objects from raw definition.
     */
    protected function initializeFields(): FieldCollection
    {
        return new FieldCollection($this->definition['fields'] ?? []);
    }

    private function throwIfEmptyValue(mixed $value, string $message): void
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            throw new \LogicException(static::class.$message);
        }
    }
}
