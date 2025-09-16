<?php

namespace Flowlight\Generator\Fields;

use Illuminate\Support\Collection;

/**
 * Specialized collection wrapper for Field objects.
 *
 * Accepts raw definition arrays (`FieldConfigArray`) keyed by field name
 * and constructs {@see Field} objects internally.
 *
 * @phpstan-import-type FieldConfigArray from \Flowlight\Generator\Config\ModelConfigWrapper
 *
 * @implements \IteratorAggregate<string, Field>
 */
class FieldCollection implements \IteratorAggregate
{
    /**
     * @var Collection<string, Field>
     */
    protected Collection $fields;

    /**
     * @param  array<string, FieldConfigArray>  $fields
     */
    public function __construct(array $fields = [])
    {
        $this->fields = new Collection;

        foreach ($fields as $name => $config) {
            $this->fields->put($name, new Field($name, $config));
        }
    }

    /**
     * @return \Traversable<string, Field>
     */
    public function getIterator(): \Traversable
    {
        return $this->fields->getIterator();
    }

    /**
     * Get all fields.
     *
     * @return Collection<string, Field>
     */
    public function all(): Collection
    {
        return $this->fields;
    }

    public function get(string $name): ?Field
    {
        return $this->fields->get($name);
    }

    public function count(): int
    {
        return $this->fields->count();
    }

    /**
     * @return Collection<int, string>
     */
    public function keys(): Collection
    {
        return $this->fields->keys();
    }

    /**
     * @return Collection<string, Field>
     */
    public function required(): Collection
    {
        return $this->fields->filter(fn (Field $f) => $f->isRequired());
    }

    /**
     * @return Collection<string, Field>
     */
    public function optional(): Collection
    {
        return $this->fields->filter(fn (Field $f) => ! $f->isRequired());
    }

    /**
     * @return Collection<string, Field>
     */
    public function ofType(string $type): Collection
    {
        return $this->fields->filter(fn (Field $f) => $f->getType() === $type);
    }
}
