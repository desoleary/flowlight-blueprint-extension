<?php

declare(strict_types=1);

namespace Flowlight\Generator\Fields;

use Flowlight\Generator\Support\TypeRuleMap;
use Illuminate\Support\Str;

/**
 * Field configuration container for Flowlight DTO generation.
 *
 * This class represents metadata for a single field in a DTO:
 * - Name
 * - Type (and PHP type mapping)
 * - Required/optional status
 * - Validation rules
 * - Validation messages
 * - Attribute label
 *
 * It provides methods for generating deterministic validation
 * rules, error messages, and code-generation-ready structures.
 *
 * @phpstan-type FieldConfigArray array{
 *     type?: string,
 *     required?: bool,
 *     length?: int,
 *     attribute?: string,
 *     rules?: list<string>,
 *     messages?: array<string,string>
 * }
 */
class Field
{
    /**
     * Priority order for validation messages.
     * Rules are always normalized in this order when possible.
     *
     * @var list<string>
     */
    protected const MESSAGE_PRIORITY = [
        'required',
        'sometimes',
        'nullable',
        'string',
        'integer',
        'numeric',
        'boolean',
        'date',
        'array',
        'email',
        'min',
        'max',
        'in',
    ];

    /**
     * Default validation message templates.
     * Placeholders:
     * - :label  → Field label
     * - :value  → Numeric/string value from rule (e.g. max:255 → 255)
     * - :values → Comma-separated list of values (e.g. in:a,b → a,b)
     *
     * @var array<string,string>
     */
    protected const DEFAULT_MESSAGES = [
        'required' => ':label is required.',
        'sometimes' => ':label is optional.',
        'string' => ':label must be text.',
        'integer' => ':label must be an integer.',
        'numeric' => ':label must be a number.',
        'boolean' => ':label must be true or false.',
        'date' => ':label must be a valid date.',
        'array' => ':label must be an array.',
        'email' => ':label must be a valid email address.',
        'max' => ':label cannot exceed :value.',
        'min' => ':label must be at least :value.',
        'in' => ':label must be one of: :values.',
    ];

    /**
     * Field name (e.g., "email").
     */
    protected string $name;

    /**
     * Raw field configuration.
     *
     * @var FieldConfigArray
     */
    protected array $config;

    /**
     * Create a new field configuration.
     *
     * @param  string  $name  The field name (e.g., "email").
     * @param  FieldConfigArray|string  $config  Field config array or shorthand type string.
     */
    public function __construct(string $name, array|string $config)
    {
        $this->name = $name;

        if (is_string($config)) {
            $config = ['type' => $config];
        }

        $this->config = $config;
    }

    /**
     * Get the field name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the configured type of the field.
     * Defaults to "string".
     */
    public function getType(): string
    {
        return $this->config['type'] ?? 'string';
    }

    /**
     * Determine whether the field is required.
     * Defaults to true.
     */
    public function isRequired(): bool
    {
        return $this->config['required'] ?? true;
    }

    /**
     * Get the maximum length (if applicable).
     * Only relevant for string/text types.
     */
    public function getLength(): ?int
    {
        return $this->config['length'] ?? null;
    }

    /**
     * Get the human-readable label for this field.
     * Defaults to a title-cased version of the field name.
     */
    public function getAttributeLabel(): string
    {
        return $this->config['attribute'] ?? Str::title(str_replace('_', ' ', $this->name));
    }

    /**
     * Get an attribute entry for DTO generation.
     *
     * @return array<string,string>
     */
    public function getAttributeEntry(): array
    {
        return [$this->name => $this->getAttributeLabel()];
    }

    /**
     * Get the validation rules for this field.
     *
     * Rule resolution order:
     * 1. Use explicit `rules` from config if present.
     * 2. Otherwise, derive defaults from {@see TypeRuleMap}.
     * 3. Ensure either "required" or "sometimes" is present.
     * 4. Append "max:{length}" for string fields with a length.
     *
     * @return list<string>
     */
    public function getRules(): array
    {
        $rules = $this->config['rules'] ?? [];

        // Merge defaults from TypeRuleMap
        $defaults = TypeRuleMap::rules($this->getType());
        $rules = array_merge($defaults, $rules);

        // Determine presence of "nullable" or "sometimes"
        $hasNullableOrSometimes = false;
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'nullable') || str_starts_with($rule, 'sometimes')) {
                $hasNullableOrSometimes = true;
                break;
            }
        }

        // Insert required/sometimes/nullable in correct order
        if (! $hasNullableOrSometimes) {
            if ($this->isRequired()) {
                array_unshift($rules, 'required');
            } else {
                array_unshift($rules, 'sometimes');
            }
        } else {
            // Move nullable/sometimes to the front
            usort($rules, function ($a, $b) {
                if ($a === 'nullable' || $a === 'sometimes') {
                    return -1;
                }
                if ($b === 'nullable' || $b === 'sometimes') {
                    return 1;
                }

                return 0;
            });
        }

        // Add max length rule for strings/text
        if ($this->getLength() !== null && in_array($this->getType(), ['string', 'text'], true)) {
            $rules[] = "max:{$this->getLength()}";
        }

        return array_values(array_unique($rules));
    }

    /**
     * Get the validation messages for this field.
     *
     * - Uses explicitly provided messages from config if available.
     * - Generates default messages using {@see DEFAULT_MESSAGES}.
     * - Ensures every rule has a corresponding message.
     * - Keys are always in the form "<field>.<rule>".
     * - Returns messages in a deterministic priority order.
     *
     * @return array<string,string>
     */
    public function getMessages(): array
    {
        /** @var array<string,string> $messages */
        $messages = $this->config['messages'] ?? [];

        $normalized = [];

        foreach ($messages as $key => $msg) {
            // If key already contains a dot (e.g. "email.required"), leave it.
            if (str_contains($key, '.')) {
                $normalized[$key] = $msg;

                continue;
            }

            // Otherwise, prefix with field name: "required" -> "email.required"
            $normalized["{$this->name}.{$key}"] = $msg;
        }

        // Fill in defaults for any rules not covered
        foreach ($this->getRules() as $rule) {
            $ruleKey = $this->getRuleKey($rule);
            $messageKey = "{$this->name}.{$ruleKey}";

            if (! isset($normalized[$messageKey])) {
                $normalized[$messageKey] = $this->generateDefaultMessage($ruleKey, $rule);
            }
        }

        return $this->normalizeMessageOrder($normalized);
    }

    /**
     * Get the resolved PHP type for this field, including nullability.
     *
     * Examples:
     *  - string
     *  - ?string
     *  - int
     *  - ?\DateTimeInterface
     *  - array
     */
    public function getPhpType(): string
    {
        return TypeRuleMap::phpType($this->getType(), ! $this->isRequired());
    }

    /**
     * Export field configuration as an enriched array,
     * including derived properties.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'phpType' => $this->getPhpType(),
            'required' => $this->isRequired(),
            'length' => $this->getLength(),
            'label' => $this->getAttributeLabel(),
            'rules' => $this->getRules(),
            'messages' => $this->getMessages(),
            'attributes' => $this->getAttributeEntry(),
        ];
    }

    /**
     * Flatten the field into a Mustache-friendly structure.
     *
     * Produces a normalized array where rules and messages
     * are expanded into lists of objects. This allows Mustache
     * templates to iterate without extra logic in the generator.
     *
     * @return array{
     *     name: string,
     *     phpType: string,
     *     attribute: string,
     *     rules: list<array{value:string}>,
     *     messages: list<array{key:string,value:string}>
     * }
     */
    public function toRendererArray(): array
    {
        $rules = [];
        foreach ($this->getRules() as $rule) {
            $rules[] = ['value' => $rule];
        }

        $messages = [];
        foreach ($this->getMessages() as $ruleKey => $message) {
            $messages[] = [
                'key' => $ruleKey,
                'value' => $message,
            ];
        }

        return [
            'name' => $this->getName(),
            'phpType' => $this->getPhpType(),
            'attribute' => $this->getAttributeLabel(),
            'rules' => $rules,
            'messages' => $messages,
        ];
    }

    /**
     * Extract the rule key from a full rule string.
     *
     * Example: "max:255" → "max"
     */
    protected function getRuleKey(string $rule): string
    {
        return Str::contains($rule, ':')
            ? Str::before($rule, ':')
            : $rule;
    }

    /**
     * Generate a default validation message for a given rule.
     *
     * Falls back to "Validation failed for :label" if the
     * rule is not in {@see DEFAULT_MESSAGES}.
     *
     * @param  string  $ruleKey  The rule key (e.g., "max").
     * @param  string  $fullRule  The full rule (e.g., "max:255").
     */
    protected function generateDefaultMessage(string $ruleKey, string $fullRule): string
    {
        $label = $this->getAttributeLabel();

        $template = self::DEFAULT_MESSAGES[$ruleKey] ?? 'Validation failed for :label.';

        $value = Str::after($fullRule, ':');
        $values = $value;

        return strtr($template, [
            ':label' => $label,
            ':value' => $value,
            ':values' => $values,
        ]);
    }

    /**
     * Normalize message ordering for deterministic output.
     *
     * Messages are grouped by field and sorted according
     * to {@see MESSAGE_PRIORITY}. Any unrecognized rules
     * are appended in alphabetical order.
     *
     * @param  array<string,string>  $messages
     * @return array<string,string>
     */
    protected function normalizeMessageOrder(array $messages): array
    {
        $grouped = [];

        foreach ($messages as $key => $message) {
            [$field, $rule] = explode('.', $key, 2);
            $grouped[$field][$rule] = $message;
        }

        $normalized = [];
        foreach ($grouped as $field => $rules) {
            // Sort by MESSAGE_PRIORITY first, then alphabetically
            uksort($rules, function ($a, $b) {
                $aIndex = array_search($a, self::MESSAGE_PRIORITY, true);
                $bIndex = array_search($b, self::MESSAGE_PRIORITY, true);

                if ($aIndex === false && $bIndex === false) {
                    return strcmp($a, $b);
                }
                if ($aIndex === false) {
                    return 1;
                }
                if ($bIndex === false) {
                    return -1;
                }

                return $aIndex <=> $bIndex;
            });

            foreach ($rules as $rule => $message) {
                $normalized["{$field}.{$rule}"] = $message;
            }
        }

        return $normalized;
    }
}
