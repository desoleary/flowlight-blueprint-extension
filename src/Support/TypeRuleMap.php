<?php

declare(strict_types=1);

namespace Flowlight\Generator\Support;

/**
 * TypeRuleMap provides the canonical mapping between
 * abstract field types (e.g., "string", "decimal", "date")
 * and their corresponding Laravel validation rules
 * and PHP type hints.
 *
 * It supports normalization of synonyms and database-like
 * column types into canonical forms. For example:
 * - "int" → "integer"
 * - "decimal" → "numeric"
 * - "datetime" → "date"
 *
 * This ensures consistent behavior across Field objects,
 * DTO generators, and validation logic.
 */
final class TypeRuleMap
{
    /**
     * Aliases for normalizing input types into canonical keys.
     *
     * @var array<string,string>
     */
    private const ALIASES = [
        'int' => 'integer',
        'decimal' => 'numeric',
        'float' => 'numeric',
        'double' => 'numeric',
        'number' => 'numeric',
        'bool' => 'boolean',
        'datetime' => 'date',
        'timestamp' => 'date',
        'varchar' => 'string',
        'char' => 'string',
        'json' => 'array',
    ];

    /**
     * Default Laravel validation rules by canonical type.
     *
     * @var array<string, list<string>>
     */
    private const MAP = [
        'string' => ['string'],
        'text' => ['string'],
        'integer' => ['integer'],
        'numeric' => ['numeric'],
        'boolean' => ['boolean'],
        'date' => ['date'],
        'array' => ['array'],
        'email' => ['string', 'email'],
    ];

    /**
     * PHP type map for DTO property declarations.
     *
     * Nullability is handled separately at runtime.
     *
     * @var array<string,string>
     */
    private const PHP_TYPES = [
        'string' => 'string',
        'text' => 'string',
        'integer' => 'int',
        'numeric' => 'float',
        'boolean' => 'bool',
        'date' => '\DateTimeInterface',
        'array' => 'array',
        'email' => 'string',
    ];

    /**
     * Normalize a raw type into its canonical form.
     *
     * Examples:
     * - "int" → "integer"
     * - "decimal" → "numeric"
     * - "datetime" → "date"
     * - "varchar" → "string"
     *
     * @param  string  $type  Raw type (possibly alias or DB type).
     * @return string Canonical type key.
     */
    private static function normalize(string $type): string
    {
        $lower = strtolower($type);

        return self::ALIASES[$lower] ?? $lower;
    }

    /**
     * Get validation rules for a given type.
     *
     * Example:
     * ```php
     * TypeRuleMap::rules('int'); // ['integer']
     * TypeRuleMap::rules('decimal'); // ['numeric']
     * TypeRuleMap::rules('email'); // ['string', 'email']
     * ```
     *
     * @param  string  $type  Raw or canonical type.
     * @return list<string> Validation rules (may be empty for unknown types).
     */
    public static function rules(string $type): array
    {
        $type = self::normalize($type);

        return self::MAP[$type] ?? [];
    }

    /**
     * Get PHP type string for a given type.
     *
     * Handles nullability by prepending "?" when required.
     *
     * Example:
     * ```php
     * TypeRuleMap::phpType('int', false);     // "int"
     * TypeRuleMap::phpType('int', true);      // "?int"
     * TypeRuleMap::phpType('decimal', false); // "float"
     * TypeRuleMap::phpType('datetime', true); // "?\DateTimeInterface"
     * ```
     *
     * @param  string  $type  Raw or canonical type.
     * @param  bool  $nullable  Whether the property is optional.
     * @return string PHP type hint string.
     */
    public static function phpType(string $type, bool $nullable): string
    {
        $type = self::normalize($type);
        $phpType = self::PHP_TYPES[$type] ?? 'string';

        return $nullable ? "?{$phpType}" : $phpType;
    }
}
