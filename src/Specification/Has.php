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
final class Has implements Specification
{
    use Composable;

    /** @var non-empty-string */
    private string $optional;

    /**
     * @param non-empty-string $optional
     */
    private function __construct(string $optional)
    {
        $this->optional = $optional;
    }

    /**
     * Use this specification to find an aggregate where the entity of the
     * specified optional has a value. If no entity exists for the optional then
     * the aggregate won't be matched.
     *
     * @psalm-pure
     *
     * @param non-empty-string $optional
     */
    public static function a(string $optional): self
    {
        return new self($optional);
    }

    /**
     * Use this specification to find an aggregate where the entity of the
     * specified optional has a value. If no entity exists for the optional then
     * the aggregate won't be matched.
     *
     * @psalm-pure
     *
     * @param non-empty-string $optional
     */
    public static function an(string $optional): self
    {
        return new self($optional);
    }

    /**
     * @return non-empty-string
     */
    public function optional(): string
    {
        return $this->optional;
    }
}
