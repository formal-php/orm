<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository\Diff;

final class Property
{
    /** @var non-empty-string */
    private string $name;
    private mixed $then;
    private mixed $now;

    /**
     * @param non-empty-string $name
     */
    private function __construct(string $name, mixed $then, mixed $now)
    {
        $this->name = $name;
        $this->then = $then;
        $this->now = $now;
    }

    /**
     * @param non-empty-string $name
     */
    public static function of(string $name, mixed $then, mixed $now): self
    {
        return new self($name, $then, $now);
    }

    /**
     * @return non-empty-string $name
     */
    public function name(): string
    {
        return $this->name;
    }

    public function then(): mixed
    {
        return $this->then;
    }

    public function now(): mixed
    {
        return $this->now;
    }

    public function changed(): bool
    {
        return $this->then !== $this->now;
    }
}
