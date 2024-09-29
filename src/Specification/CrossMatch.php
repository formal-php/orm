<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Formal\ORM\Adapter\Repository\SubMatch;
use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};

/**
 * @internal
 * @psalm-immutable
 */
final class CrossMatch implements Comparator
{
    use Composable;

    /** @var non-empty-string */
    private string $property;
    private SubMatch $value;

    /**
     * @param non-empty-string $property
     */
    private function __construct(
        string $property,
        SubMatch $value,
    ) {
        $this->property = $property;
        $this->value = $value;
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(
        string $property,
        SubMatch $value,
    ): self {
        return new self($property, $value);
    }

    public function property(): string
    {
        return $this->property;
    }

    public function sign(): Sign
    {
        return Sign::in;
    }

    public function value(): mixed
    {
        return $this->value->unwrap();
    }
}
