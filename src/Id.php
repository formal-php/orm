<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Ramsey\Uuid\Uuid;

/**
 * @template T of object
 * @psalm-immutable
 */
final class Id
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function new(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    /**
     * @psalm-pure
     *
     * @throws \LogicException When not a valid id
     */
    public static function of(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \LogicException("Invalid id '$value'");
        }

        return new self($value);
    }

    public function equals(self $id): bool
    {
        return $this->value === $id->value;
    }

    /**
     * @internal
     */
    public function toString(): string
    {
        return $this->value;
    }
}
