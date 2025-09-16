<?php

namespace Flowlight\Generator\Config;

/**
 * DTO-specific configuration wrapper.
 *
 * Provides default namespace, class name, and base class for generated DTOs.
 */
class DtoConfig extends ModelConfigWrapper
{
    /**
     * {@inheritDoc}
     */
    protected function getDefaultNamespace(): string
    {
        return "App\\Domain\\{$this->modelName}s\\Data";
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultClassName(): string
    {
        return $this->modelName.'Data';
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultExtendedClassName(): ?string
    {
        return 'Flowlight\\BaseData';
    }
}
