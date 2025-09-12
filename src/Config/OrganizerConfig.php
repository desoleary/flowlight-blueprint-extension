<?php

namespace Flowlight\Generator\Config;

/**
 * @phpstan-type OrganizerConfigArray array<string,bool>|true|false
 */
class OrganizerConfig
{
    /** @var OrganizerConfigArray */
    protected array|bool $config;

    protected string $modelName;

    /**
     * @param  OrganizerConfigArray  $config
     */
    public function __construct(array|bool $config, string $modelName)
    {
        $this->config = $config;
        $this->modelName = $modelName;
    }

    /**
     * @return OrganizerConfigArray
     */
    public function getConfig(): array|bool
    {
        return $this->config;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }
}
