<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

use Innmind\Immutable\{
    Set,
    Maybe,
};

final class Optional
{
    /** @var non-empty-string */
    private string $name;
    /** @var Maybe<Set<Property>> */
    private Maybe $properties;

    /**
     * @param non-empty-string $name
     * @param Maybe<Set<Property>> $properties
     */
    private function __construct(string $name, Maybe $properties)
    {
        $this->name = $name;
        $this->properties = $properties;
    }

    /**
     * @param non-empty-string $name
     * @param Maybe<Set<Property>> $properties
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
     * @return Maybe<Set<Property>>
     */
    public function properties(): Maybe
    {
        return $this->properties;
    }

    public function referenceSame(self|Optional\BrandNew $optional): bool
    {
        return $this->name === $optional->name();
    }
}
