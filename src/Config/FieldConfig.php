<?php

namespace Flowlight\Generator\Config;

use Illuminate\Support\Str;

/**
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
    protected string $name;

    /** @var FieldConfigArray */
    protected array $config;

    /**
     * @param  FieldConfigArray  $config
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        /** @var string|null $type */
        $type = $this->config['type'] ?? null;

        return $type ?? 'string';
    }

    public function isRequired(): bool
    {
        /** @var bool|null $req */
        $req = $this->config['required'] ?? null;

        return $req ?? true;
    }

    public function getLength(): ?int
    {
        /** @var int|null $len */
        $len = $this->config['length'] ?? null;

        return $len;
    }

    public function getAttributeLabel(): string
    {
        /** @var string|null $attr */
        $attr = $this->config['attribute'] ?? null;

        return $attr ?? Str::title(str_replace('_', ' ', $this->name));
    }

    /**
     * @return list<string>
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
     * @return array<string,string>
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

    protected function getRuleKey(string $rule): string
    {
        return Str::contains($rule, ':') ? Str::before($rule, ':') : $rule;
    }

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

    protected function generateMaxMessage(string $rule, string $label): string
    {
        $value = Str::after($rule, ':');

        return "{$label} cannot exceed {$value}.";
    }

    protected function generateMinMessage(string $rule, string $label): string
    {
        $value = Str::after($rule, ':');

        return "{$label} must be at least {$value}.";
    }

    protected function generateInMessage(string $rule, string $label): string
    {
        $values = Str::after($rule, ':');

        return "{$label} must be one of: {$values}.";
    }
}
