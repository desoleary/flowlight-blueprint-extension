<?php

namespace Flowlight\Generator\Config;

use Illuminate\Support\Str;

/**
 * Field configuration container for Flowlight DTO generation.
 *
 * This class holds configuration metadata for a single field
 * within a DTO, including its type, validation rules, messages,
 * and presentation details (e.g., attribute label).
 *
 * It provides defaults for type (`string`), required state (`true`),
 * and humanized labels (based on the field name). Validation rules
 * and messages can be overridden or automatically generated based
 * on configuration and conventions.
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
class FieldConfig
{
    /**
     * The field name (typically corresponds to the model/DTO property).
     */
    protected string $name;

    /**
     * Raw configuration for this field.
     *
     * Keys can include:
     * - `type`      (string)  : Field type, e.g. "string", "integer".
     * - `required`  (bool)    : Whether the field is required.
     * - `length`    (int)     : Maximum length (applies to string/text).
     * - `attribute` (string)  : Custom human-readable label.
     * - `rules`     (string[]) : Explicit validation rules.
     * - `messages`  (array<string,string>) : Custom error messages.
     *
     * @var FieldConfigArray
     */
    protected array $config;

    /**
     * Create a new field configuration.
     *
     * @param  string  $name  The field name (e.g., "email").
     * @param  FieldConfigArray  $config  The raw configuration array.
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
    }

    /**
     * Get the field name.
     *
     * @return string The configured field name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the field type.
     *
     * Defaults to `"string"` if not specified.
     *
     * @return string The field type.
     */
    public function getType(): string
    {
        /** @var string|null $type */
        $type = $this->config['type'] ?? null;

        return $type ?? 'string';
    }

    /**
     * Determine whether the field is required.
     *
     * Defaults to `true` if not specified.
     *
     * @return bool True if required, false otherwise.
     */
    public function isRequired(): bool
    {
        /** @var bool|null $req */
        $req = $this->config['required'] ?? null;

        return $req ?? true;
    }

    /**
     * Get the maximum length for the field.
     *
     * Typically used with string/text types to enforce a "max" rule.
     *
     * @return int|null The maximum length, or null if not set.
     */
    public function getLength(): ?int
    {
        /** @var int|null $len */
        $len = $this->config['length'] ?? null;

        return $len;
    }

    /**
     * Get the human-readable attribute label.
     *
     * - Defaults to a title-cased version of the field name with underscores replaced by spaces.
     * - Can be overridden via the `attribute` key in config.
     *
     * @return string Attribute label.
     */
    public function getAttributeLabel(): string
    {
        /** @var string|null $attr */
        $attr = $this->config['attribute'] ?? null;

        return $attr ?? Str::title(str_replace('_', ' ', $this->name));
    }

    /**
     * Get the validation rules for this field.
     *
     * Rule resolution order:
     * 1. Use explicitly provided `rules` if present.
     * 2. Otherwise, pull default rules from `config('flowlight.field_types')` by type.
     * 3. Ensure `required` or `sometimes` is applied depending on `isRequired()`.
     * 4. Append `max:{length}` if applicable (for string/text fields with a length).
     * 5. Ensure uniqueness of rules.
     *
     * @return list<string> A list of validation rule strings.
     */
    public function getRules(): array
    {
        /** @var list<string> $rules */
        $rules = $this->config['rules'] ?? [];

        /** @var array<string,array{default_rules?:list<string>}> $fieldTypes */
        $fieldTypes = (array) config('flowlight.field_types', []);

        if ($rules === [] && isset($fieldTypes[$this->getType()]['default_rules'])) {
            $rules = array_merge($rules, $fieldTypes[$this->getType()]['default_rules']);
        }

        if ($this->isRequired() && ! in_array('sometimes', $rules, true) && ! in_array('nullable', $rules, true)) {
            array_unshift($rules, 'required');
        } elseif (! $this->isRequired() && ! in_array('sometimes', $rules, true)) {
            array_unshift($rules, 'sometimes');
        }

        if ($this->getLength() !== null && in_array($this->getType(), ['string', 'text'], true)) {
            $rules[] = "max:{$this->getLength()}";
        }

        /** @var list<string> $unique */
        $unique = array_values(array_unique($rules));

        return $unique;
    }

    /**
     * Get the validation messages for this field.
     *
     * - Uses explicitly provided messages if available.
     * - Otherwise, generates default messages for each validation rule.
     * - Ensures every rule has an associated message.
     *
     * @return array<string,string> Map of rule keys to error messages.
     */
    public function getMessages(): array
    {
        /** @var array<string,string> $messages */
        $messages = $this->config['messages'] ?? [];

        foreach ($this->getRules() as $rule) {
            $ruleKey = $this->getRuleKey($rule);
            if (! isset($messages[$ruleKey])) {
                $messages[$ruleKey] = $this->generateDefaultMessage($ruleKey, $rule);
            }
        }

        return $messages;
    }

    /**
     * Extract the rule key from a validation rule string.
     *
     * Examples:
     * - `"max:255"` → `"max"`
     * - `"string"`  → `"string"`
     *
     * @param  string  $rule  The validation rule.
     * @return string The rule key.
     */
    protected function getRuleKey(string $rule): string
    {
        return Str::contains($rule, ':') ? Str::before($rule, ':') : $rule;
    }

    /**
     * Generate a default validation message for a given rule.
     *
     * @param  string  $ruleKey  The key of the rule (e.g., "required", "max").
     * @param  string  $fullRule  The full validation rule string.
     * @return string Human-readable validation message.
     */
    protected function generateDefaultMessage(string $ruleKey, string $fullRule): string
    {
        $label = $this->getAttributeLabel();

        return match ($ruleKey) {
            'required' => "{$label} is required.",
            'email' => 'Please provide a valid email address.',
            'numeric' => "{$label} must be a number.",
            'integer' => "{$label} must be an integer.",
            'string' => "{$label} must be text.",
            'boolean' => "{$label} must be true or false.",
            'date' => "{$label} must be a valid date.",
            'max' => $this->generateMaxMessage($fullRule, $label),
            'min' => $this->generateMinMessage($fullRule, $label),
            'in' => $this->generateInMessage($fullRule, $label),
            default => "Validation failed for {$label}.",
        };
    }

    /**
     * Generate a default "max" validation message.
     *
     * @param  string  $rule  Full rule string, e.g. "max:255".
     * @param  string  $label  Field label.
     * @return string Error message.
     */
    protected function generateMaxMessage(string $rule, string $label): string
    {
        $value = Str::after($rule, ':');

        return "{$label} cannot exceed {$value}.";
    }

    /**
     * Generate a default "min" validation message.
     *
     * @param  string  $rule  Full rule string, e.g. "min:3".
     * @param  string  $label  Field label.
     * @return string Error message.
     */
    protected function generateMinMessage(string $rule, string $label): string
    {
        $value = Str::after($rule, ':');

        return "{$label} must be at least {$value}.";
    }

    /**
     * Generate a default "in" validation message.
     *
     * @param  string  $rule  Full rule string, e.g. "in:admin,user".
     * @param  string  $label  Field label.
     * @return string Error message.
     */
    protected function generateInMessage(string $rule, string $label): string
    {
        $values = Str::after($rule, ':');

        return "{$label} must be one of: {$values}.";
    }
}
