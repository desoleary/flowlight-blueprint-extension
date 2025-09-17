<?php

declare(strict_types=1);

namespace Flowlight\Generator\Support;

use Mustache\Engine as MustacheEngine;

/**
 * TemplateRenderer wraps the Mustache engine and provides
 * a consistent interface for rendering generator templates.
 */
class TemplateRenderer
{
    protected MustacheEngine $engine;

    public function __construct(?MustacheEngine $engine = null)
    {
        $this->engine = $engine ?? new MustacheEngine([
            'escape' => fn ($value) => $value, // disable escaping for codegen
        ]);
    }

    /**
     * Render a Mustache template with the given context.
     *
     * @param  string  $template  Mustache template string
     * @param  array<string,mixed>  $context  Context data
     */
    public function render(string $template, array $context): string
    {
        return $this->engine->render($template, $context);
    }
}
