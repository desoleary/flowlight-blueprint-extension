<?php

namespace Flowlight\Generator\Config;

/**
 * Organizer-specific configuration wrapper.
 *
 * Provides defaults for Organizer namespace/class/extends
 * and exposes helper methods for supported operation types.
 *
 * Example draft.yaml:
 * api:
 *   User:
 *     fields:
 *       name: string
 *     organizers:
 *       create: true
 *       update: false
 *       list: true
 */
class OrganizerConfig extends ModelConfigWrapper
{
    /**
     * {@inheritDoc}
     */
    protected function getDefaultNamespace(): string
    {
        return "App\\Domain\\{$this->modelName}s\\Organizers";
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultClassName(): string
    {
        return $this->modelName.'Organizer';
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultExtendedClassName(): ?string
    {
        return 'Flowlight\\LightService\\Organizer';
    }
}
