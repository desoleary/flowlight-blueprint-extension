<?php

namespace Flowlight\Generator\Config;

/**
 * Organizer configuration container for Flowlight code generation.
 *
 * This class encapsulates configuration for which organizer types
 * (e.g., create, read, update, delete, list) should be generated
 * for a given model. It allows enabling all organizers with `true`,
 * disabling entirely with `false`, or providing a keyed array to
 * selectively enable or disable specific organizers.
 *
 * @phpstan-type OrganizerConfigArray array<string,bool>|true|false
 */
class OrganizerConfig
{
    /**
     * Raw organizer configuration.
     *
     * - `true`  : all organizer types should be generated.
     * - `false` : no organizers should be generated.
     * - `array<string,bool>` : explicit map of organizer types to enable/disable.
     *
     * @var OrganizerConfigArray
     */
    protected array|bool $config;

    /**
     * The model name associated with this organizer configuration.
     */
    protected string $modelName;

    /**
     * Create a new organizer configuration wrapper.
     *
     * @param  OrganizerConfigArray  $config  The raw organizer configuration.
     * @param  string  $modelName  The model name this config applies to.
     */
    public function __construct(array|bool $config, string $modelName)
    {
        $this->config = $config;
        $this->modelName = $modelName;
    }

    /**
     * Get the raw organizer configuration.
     *
     * @return OrganizerConfigArray The stored configuration (true, false, or array).
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
}
