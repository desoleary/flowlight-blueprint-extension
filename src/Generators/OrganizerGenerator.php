<?php

namespace Flowlight\Generator\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Flowlight\Generator\Config\ModelConfigWrapper;
use Illuminate\Filesystem\Filesystem;

/**
 * @phpstan-import-type ModelConfigArray from ModelConfigWrapper
 */
class OrganizerGenerator implements Generator
{
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return ['api'];
    }

    /**
     * @return array<string,string> modelName => generated info (for now, placeholder)
     */
    public function output(Tree $tree): array
    {
        $output = [];

        /** @var array<string, ModelConfigArray> $models */
        $models = $tree->models();

        foreach ($models as $modelName => $modelConfig) {
            // @phpstan-var ModelConfigArray $modelConfig
            $wrapper = new ModelConfigWrapper($modelName, $modelConfig);

            if ($wrapper->shouldGenerateOrganizers()) {
                // Placeholder logic until we add actual stub file generation
                $output[$modelName] = "Organizers for {$modelName} would be generated here.";
            }
        }

        return $output;
    }
}
