<?php

namespace Flowlight\Generator\Config;

/**
 * Data Transfer Object (DTO) configuration container.
 *
 * This class represents configuration options for generating DTO classes
 * within the Flowlight code generation system. It provides defaults for
 * namespace, class name, and base class, while allowing overrides via
 * configuration arrays.
 *
 * @phpstan-type DtoConfigArray array{
 *     namespace?: string,
 *     className?: string,
 *     extends?: string,
 *     messages?: array<string,string>
 * }|true|array<string,mixed>
 */
class DtoConfig
{
    /**
     * Raw configuration array or boolean flag.
     *
     * - `true` indicates that defaults should be used.
     * - An array allows overriding namespace, className, extends, and messages.
     *
     * @var DtoConfigArray
     */
    protected array|bool $config;

    /**
     * The model name associated with this DTO configuration.
     *
     * Used as the basis for default namespace and class name resolution.
     */
    protected string $modelName;

    /**
     * Create a new DTO configuration container.
     *
     * @param  DtoConfigArray  $config  The raw configuration (true for defaults or array for overrides).
     * @param  string  $modelName  The name of the model this DTO belongs to.
     */
    public function __construct(array|bool $config, string $modelName)
    {
        $this->config = $config;
        $this->modelName = $modelName;
    }

    /**
     * Get the raw configuration array or `true` if defaults are used.
     *
     * @return array<string,mixed>|true
     */
    public function getConfig(): array|bool
    {
        return $this->config;
    }

    /**
     * Get the model name associated with this configuration.
     *
     * @return string The model name.
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * Resolve the namespace for the DTO class.
     *
     * - Defaults to `App\Domain\{ModelName}s\Data`.
     * - Can be overridden via the `namespace` key in config.
     *
     * @return string Fully-qualified namespace for the DTO.
     */
    public function getNamespace(): string
    {
        if ($this->config === true) {
            return "App\\Domain\\{$this->modelName}s\\Data";
        }

        return isset($this->config['namespace']) && is_string($this->config['namespace'])
            ? $this->config['namespace']
            : "App\\Domain\\{$this->modelName}s\\Data";
    }

    /**
     * Resolve the class name for the DTO.
     *
     * - Defaults to `{ModelName}Data`.
     * - Can be overridden via the `className` key in config.
     *
     * @return string Class name for the DTO.
     */
    public function getClassName(): string
    {
        if ($this->config === true) {
            return "{$this->modelName}Data";
        }

        return isset($this->config['className']) && is_string($this->config['className'])
            ? $this->config['className']
            : "{$this->modelName}Data";
    }

    /**
     * Resolve the parent class the DTO should extend.
     *
     * - Defaults to `Flowlight\BaseData`.
     * - Can be overridden via the `extends` key in config.
     *
     * @return string Fully-qualified class name of the parent.
     */
    public function getExtends(): string
    {
        if ($this->config === true) {
            return 'Flowlight\\BaseData';
        }

        return isset($this->config['extends']) && is_string($this->config['extends'])
            ? $this->config['extends']
            : 'Flowlight\\BaseData';
    }

    /**
     * Get custom validation or error messages for the DTO.
     *
     * - Defaults to an empty array.
     * - Must be an array of string keys and string values.
     * - If any invalid types are found, returns an empty array instead.
     *
     * @return array<string,string> Map of custom messages.
     */
    public function getCustomMessages(): array
    {
        if ($this->config === true) {
            return [];
        }

        if (isset($this->config['messages']) && is_array($this->config['messages'])) {
            // Filter to only keep string=>string pairs
            $messages = array_filter(
                $this->config['messages'],
                fn ($v, $k) => is_string($k) && is_string($v),
                ARRAY_FILTER_USE_BOTH
            );

            // Only return if all entries were valid
            if ($messages === $this->config['messages']) {
                /** @var array<string,string> $messages */
                return $messages;
            }

            return [];
        }

        return [];
    }
}
