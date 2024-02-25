<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

use Innmind\Immutable\{
    Sequence,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Optional
{
    /** @var non-empty-string */
    private string $name;
    /** @var Maybe<Sequence<Property>> */
    private Maybe $properties;

    /**
     * @param non-empty-string $name
     * @param Maybe<Sequence<Property>> $properties
     */
    private function __construct(string $name, Maybe $properties)
    {
        $this->name = $name;
        $this->properties = $properties;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param Maybe<Sequence<Property>> $properties
     */
    public static function of(string $name, Maybe $properties): self
    {
        return new self($name, $properties);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Maybe<Sequence<Property>>
     */
    public function properties(): Maybe
    {
        return $this->properties;
    }
}
