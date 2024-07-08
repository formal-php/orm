<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Innmind\Specification\{
    Specification,
    Composable,
};

/**
 * @psalm-immutable
 */
final class Just implements Specification
{
    use Composable;

    /** @var non-empty-string */
    private string $optional;
    private Specification $specification;

    /**
     * @param non-empty-string $optional
     */
    private function __construct(string $optional, Specification $specification)
    {
        $this->optional = $optional;
        $this->specification = $specification;
    }

    /**
     * Use this specification to find an aggregate where the entity of the
     * specified optional matches the given specification. If no entity exists
     * for the optional then the aggregate won't be matched.
     *
     * @psalm-pure
     *
     * @param non-empty-string $optional
     */
    public static function of(string $optional, Specification $specification): self
    {
        return new self($optional, $specification);
    }

    /**
     * @return non-empty-string
     */
    public function optional(): string
    {
        return $this->optional;
    }

    public function specification(): Specification
    {
        return $this->specification;
    }
}
