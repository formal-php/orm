<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate\Collection\Entity;

use Ramsey\Uuid\{
    UuidInterface,
    Uuid,
};

/**
 * @psalm-immutable
 */
final class Reference
{
    private UuidInterface $id;

    private function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    public static function new(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * @psalm-pure
     */
    public static function of(string $string): self
    {
        return new self(Uuid::fromString($string));
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->id->toString();
    }
}
