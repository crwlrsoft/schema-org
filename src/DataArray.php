<?php

namespace Crwlr\SchemaOrg;

use Iterator;

/**
 * @implements Iterator<int|string, mixed>
 */

final class DataArray implements Iterator
{
    /**
     * @param mixed[] $data
     */
    public function __construct(private array $data)
    {
        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                $this->data[$key] = new self($value);
            }
        }
    }

    /**
     * @param mixed[] $data
     * @return self
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    public function current(): mixed
    {
        return current($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function key(): int|string|null
    {
        return key($this->data);
    }

    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * @return mixed[]
     */
    public function toArray(bool $recursive = false): array
    {
        if ($recursive) {
            foreach ($this->data as $key => $value) {
                if ($value instanceof DataArray) {
                    $this->data[$key] = $value->toArray(true);
                }
            }
        }

        return $this->data;
    }

    public function set(int|string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @return string|mixed[]|null
     */
    public function getType(): string|array|null
    {
        if (
            !isset($this->data['@type']) ||
            (!is_string($this->data['@type']) && !$this->data['@type'] instanceof DataArray)
        ) {
            return null;
        }

        return $this->data['@type'] instanceof DataArray ? $this->data['@type']->toArray() : $this->data['@type'];
    }

    /**
     * @return DataArray
     */
    public function getGraph(): DataArray
    {
        return $this->hasGraphKey() ? $this->data['@graph'] : new self([]);
    }

    public function isSchemaOrgJsonLdData(): bool
    {
        return $this->hasSchemaOrgContext() && ($this->hasTypeKey() || $this->hasGraphKey());
    }

    public function hasSchemaOrgContext(): bool
    {
        return isset($this->data['@context']) && str_contains($this->data['@context'], 'schema.org');
    }

    public function hasTypeKey(): bool
    {
        return isset($this->data['@type']);
    }

    public function hasGraphKey(): bool
    {
        return isset($this->data['@graph']);
    }
}
