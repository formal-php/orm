<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

final class Property
{
    /** @var non-empty-string */
    private string $name;
    private null|string|int|bool $value;

    /**
     * @param non-empty-string $name
     */
    private function __construct(string $name, null|string|int|bool $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param non-empty-string $name
     */
    public static function of(string $name, null|string|int|bool $value): self
    {
        return new self($name, $value);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function value(): null|string|int|bool
    {
        return $this->value;
    }

    public function referenceSame(self $property): bool
    {
        return $this->name === $property->name();
    }
}
