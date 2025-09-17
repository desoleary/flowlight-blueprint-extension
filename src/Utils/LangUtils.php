<?php

declare(strict_types=1);

namespace Flowlight\Generator\Utils;

/**
 * LangUtils — helpers for working with language primitives.
 *
 * Provides safe extraction of short class names from objects or FQCN strings.
 *
 * Examples:
 * ```php
 * LangUtils::toClassName(Foo::class);          // "Foo"
 * LangUtils::toClassName(new Foo());           // "Foo"
 * LangUtils::toClassName('App\\Domain\\Bar');  // "Bar"
 * LangUtils::toClassName('Nonexistent\\Baz');  // "Baz"
 * ```
 */
final class LangUtils
{
    /**
     * Get the short class name (no namespace) from a FQCN or object.
     *
     * If the class does not exist, it will fall back to string splitting.
     *
     * @param  object|string  $value  An object instance or class name (FQCN).
     *
     * @phpstan-param object|string $value
     *
     * @return string The base class name without namespace, or empty string if not resolvable.
     */
    public static function toClassName(object|string $value): string
    {
        $fqcn = \is_object($value) ? $value::class : (string) $value;

        if ($fqcn === '') {
            return '';
        }

        // Reflection if class/interface exists
        if (\class_exists($fqcn) || \interface_exists($fqcn)) {
            return (new \ReflectionClass($fqcn))->getShortName();
        }

        // Fallback: split namespace manually
        $parts = \explode('\\', $fqcn);

        return \array_pop($parts) ?: '';
    }
}
