<?php
declare(strict_types = 1);

namespace Formal\ORM\Sort;

use Formal\ORM\Sort as Direction;

/**
 * @psalm-immutable
 */
final class Property
{
    /**
     * @param non-empty-string $name
     */
    private function __construct(
        private string $name,
        private Direction $direction,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function of(string $name, Direction $direction): self
    {
        return new self($name, $direction);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function direction(): Direction
    {
        return $this->direction;
    }
}
