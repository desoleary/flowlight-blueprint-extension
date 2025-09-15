<?php

namespace Flowlight\Generator\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Flowlight\Generator\Config\ModelConfigWrapper;
use Illuminate\Filesystem\Filesystem;

/**
 * Organizer Generator for Flowlight API scaffolding.
 *
 * Implements Blueprint's {@see Generator} contract to generate
 * organizer classes (e.g., Create, Read, Update, Delete, List)
 * for API models based on the parsed Blueprint tree.
 *
 * Currently, this class serves as a placeholder with minimal
 * logic: it only indicates which organizers would be generated.
 * Stub-based file generation will be added in future iterations,
 * similar to {@see DtoGenerator}.
 *
 * @phpstan-import-type ModelConfigArray from ModelConfigWrapper
 */
class OrganizerGenerator implements Generator
{
    /**
     * Filesystem instance for reading/writing organizer files.
     *
     * Not yet used in the placeholder implementation.
     */
    protected Filesystem $filesystem;

    /**
     * Create a new Organizer generator.
     *
     * @param  Filesystem  $filesystem  The filesystem instance.
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Get the generator types this class handles.
     *
     * Required by the Blueprint contract. This generator only applies
     * to `api` types.
     *
     * @return list<string> List of types handled.
     */
    public function types(): array
    {
        return ['api'];
    }

    /**
     * Generate organizer scaffolding for models in the Blueprint tree.
     *
     * Placeholder logic: instead of creating files, this method returns
     * a string message per model indicating organizers would be generated.
     *
     * @param  Tree  $tree  The parsed Blueprint tree.
     * @return array<string,string> Map of model name => generated info string.
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
                // Placeholder logic until actual stub generation is added
                $output[$modelName] = "Organizers for {$modelName} would be generated here.";
            }
        }

        return $output;
    }
}
