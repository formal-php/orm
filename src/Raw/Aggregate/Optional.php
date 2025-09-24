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
    /**
     * @param non-empty-string $name
     * @param Maybe<Sequence<Property>> $properties
     */
    private function __construct(
        private string $name,
        private Maybe $properties,
    ) {
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
