<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Property;

final class Type
{
    /** @var class-string */
    private string $class;
    private string $property;

    /**
     * @param class-string $class
     */
    private function __construct(string $class, string $property)
    {
        $this->class = $class;
        $this->property = $property;
    }

    /**
     * @param class-string $class
     */
    public static function of(string $class, string $property): self
    {
        return new self($class, $property);
    }

    /**
     * @param class-string $class
     */
    public function ofClass(string $class): bool
    {
        $type = $this->reflection()->getType();

        if (\is_null($type)) {
            return false;
        }

        if ($type->allowsNull()) {
            return false;
        }

        $type = (string) $type;

        if (!\class_exists($type)) {
            return false;
        }

        return $type === $class;
    }

    private function reflection(): \ReflectionProperty
    {
        return new \ReflectionProperty($this->class, $this->property);
    }
}
