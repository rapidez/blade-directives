<?php

namespace Rapidez\BladeDirectives;

use ArrayAccess;
use ArrayObject;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\CanBeEscapedWhenCastToString;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class OptionalDeep implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable, CanBeEscapedWhenCastToString, Arrayable
{
    use Macroable {
        __call as macroCall;
    }

    protected $value;

    public function __construct($value)
    {
        $this->value = $value instanceof static ? $value->get() : $value;
    }

    public function __get($key): static
    {
        return new static($this->get($key));
    }

    public function get($key = null, $default = null): mixed
    {
        return data_get($this->value, $key, $default);
    }

    public function __set($key, $value): void
    {
        if (!is_object($this->value) && !is_array($this->value)) {
            return;
        }

        $unwrappedValue = $value instanceof static ? $value->value : $value;

        data_set($this->value, $key, $unwrappedValue);
    }

    public function __unset($key): void
    {
        if (!is_object($this->value) && !is_array($this->value)) {
            return;
        }

        data_forget($this->value, $key);
    }

    public function __toString(): string
    {
        if (!isset($this->value)) {
            return '';
        }

        if (is_string($this->value)) {
            return $this->value;
        }

        return strval($this->value);
    }

    public function __invoke($default = null): mixed
    {
        return $this->get(null, $default);
    }

    public function __isset($key)
    {
        return isset($this->value->{$key});
    }

    public function isset(): bool
    {
        return isset($this->value);
    }

    public function __isNotEmpty(): bool
    {
        if (is_object($this->value) && method_exists($this->value, 'value')) {
            return boolval($this->value->value());
        }

        return boolval($this->value);
    }

    public function __isEmpty(): bool
    {
        return !$this->isNotEmpty();
    }

    /**
     * Attempt to cast the optionalDeep object to a boolean.
     * This is not a real magic method, but common practice in other frameworks.
     * see: https://wiki.php.net/rfc/objects-can-be-falsifiable
     */
    public function __toBool(): bool
    {
        if (is_bool($this->value)) {
            return $this->value;
        }

        if (!$this->isset()) {
            return false;
        }

        if ($this->__isEmpty()) {
            return false;
        }

        if (is_object($this->value) && method_exists($this->value, 'toBool')) {
            return $this->value->toBool();
        }

        if (is_object($this->value) && method_exists($this->value, '__toBool')) {
            return $this->value->__toBool();
        }

        // Is not empty, thus true.
        return true;
    }

    // ArrayAccess interface
    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->value->{$offset}) || isset($this->value[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }

    /**
     * Attempt to cast the optionalDeep object to an array.
     * This is not a real magic method, but common practice in other frameworks.
     * see: https://wiki.php.net/rfc/to-array
     */
    public function __toArray(): array
    {
        if (is_array($this->value)) {
            return $this->value;
        }

        if ($this->value instanceof \Traversable) {
            $return = [];
            foreach ($this->value as $key => $value) {
                $return[$key] = $value;
            }

            return $return;
        }

        if (is_object($this->value) && method_exists($this->value, 'toArray')) {
            return $this->value->toArray();
        }

        if (is_object($this->value) && method_exists($this->value, '__toArray')) {
            return $this->value->__toArray();
        }

        return Arr::wrap($this->value);
    }

    // IteratorAggregate interface
    public function getIterator(): Traversable
    {
        if (is_iterable($this->value)) {
            return collect($this->value)->map(fn($item) => new static($item));
        }

        return new ArrayObject();
    }

    // Countable interface
    public function count(): int
    {
        if ($this->value instanceof Countable) {
            return count($this->value);
        }

        return 0;
    }

    // JsonSerializable interface
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    // CanBeEscapedWhenCastToString interface
    // This function gets called exclusively when passing props to a Blade component
    // Here we hack it to return the full object rather than an escaped string when the value isn't stringable
    public function escapeWhenCastingToString($escape = true)
    {
        if ($this->value instanceof Stringable
            || is_string($this->value)
            || is_scalar($this->value)
            || $this->value === null
        ) {
            return e($this->value);
        }

        return $this;
    }

    // Arrayable interface
    public function toArray(): array
    {
        return $this->__toArray();
    }

    public function __call($method, $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (!is_object($this->value) || !is_string($this->value) || !method_exists($this->value, $method)) {
            if ($method == 'isNotEmpty') {
                return $this->__isNotEmpty();
            }
            if ($method == 'isEmpty') {
                return $this->__isEmpty();
            }
            if ($method == 'toBool') {
                return $this->__toBool();
            }
            if ($method == 'toString') {
                return $this->__toString();
            }

            return new static(null);
        }

        return new static($this->value->{$method}($parameters));
    }
}
