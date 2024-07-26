<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

final class Aggregates
{
    private Types $types;
    /** @var ?callable(class-string): non-empty-string  */
    private $mapName;

    /**
     * @param callable(class-string): non-empty-string $mapName
     */
    private function __construct(Types $types, ?callable $mapName)
    {
        $this->types = $types;
        $this->mapName = $mapName;
    }

    public static function of(Types $types): self
    {
        return new self($types, null);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(class-string): non-empty-string $map
     */
    public function mapName(callable $map): self
    {
        return new self($this->types, $map);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return Aggregate<T>
     */
    public function get(string $class): Aggregate
    {
        return Aggregate::of(
            $this->types,
            $this->mapName,
            $class,
        );
    }
}
