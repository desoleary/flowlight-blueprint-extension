<?php

namespace Flowlight\Generator\Config;

/**
 * @phpstan-type DtoConfigArray array{
 *     namespace?: string,
 *     className?: string,
 *     extends?: string,
 *     messages?: array<string,string>
 * }|true|array<string,mixed>
 */
class DtoConfig
{
    /** @var DtoConfigArray */
    protected array|bool $config;

    protected string $modelName;

    /**
     * @param  DtoConfigArray  $config
     */
    public function __construct(array|bool $config, string $modelName)
    {
        $this->config = $config;
        $this->modelName = $modelName;
    }

    /**
     * @return array<string,mixed>|true
     */
    public function getConfig(): array|bool
    {
        return $this->config;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getNamespace(): string
    {
        if ($this->config === true) {
            return "App\\Domain\\{$this->modelName}s\\Data";
        }

        return isset($this->config['namespace']) && is_string($this->config['namespace'])
            ? $this->config['namespace']
            : "App\\Domain\\{$this->modelName}s\\Data";
    }

    public function getClassName(): string
    {
        if ($this->config === true) {
            return "{$this->modelName}Data";
        }

        return isset($this->config['className']) && is_string($this->config['className'])
            ? $this->config['className']
            : "{$this->modelName}Data";
    }

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
     * @return array<string,string>
     */
    public function getCustomMessages(): array
    {
        if ($this->config === true) {
            return [];
        }

        if (isset($this->config['messages']) && is_array($this->config['messages'])) {
            /** @var array<string,string> $messages */
            $messages = $this->config['messages'];

            return $messages;
        }

        return [];
    }
}
