<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\Id as Identifier;
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategy,
};

/**
 * @internal
 * @template T of object
 */
final class Id
{
    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function property(): string
    {
        return $this->property;
    }

    /**
     * @param T $aggregate
     *
     * @return Identifier<T>
     */
    public function extract(object $aggregate): Identifier
    {
        /** @var Identifier<T> */
        return ReflectionObject::of(
            $aggregate,
            null,
            null,
            new ExtractionStrategy\ReflectionStrategy,
        )
            ->extract($this->property)
            ->get($this->property);
    }
}
