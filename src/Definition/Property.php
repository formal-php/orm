<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\{
    Definition\Property\Type,
    Id,
};
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
final class Property
{
    private string $class;
    private string $name;
    private Type $type;

    private function __construct(string $class, string $name, Type $type)
    {
        $this->class = $class;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @param class-string $class
     */
    public static function of(string $class, string $property): self
    {
        return new self(
            $class,
            $property,
            Type::of($class, $property),
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): Type
    {
        return $this->type;
    }

    /**
     * If it's the id to reference the aggregate.
     *
     * Ids that references relations will return false
     */
    public function isId(): bool
    {
        return $this->type->ofClass(Id::class) &&
            $this
                ->template()
                ->filter(fn($template) => $template->of($this->class))
                ->match(
                    static fn() => true,
                    static fn() => false,
                );
    }

    /**
     * @return Maybe<Template>
     */
    private function template(): Maybe
    {
        $reflection = new \ReflectionProperty($this->class, $this->name);

        foreach ($reflection->getAttributes(Template::class) as $attribute) {
            /** @var Maybe<Template> */
            return Maybe::just($attribute->newInstance());
        }

        /** @var Maybe<Template> */
        return Maybe::nothing();
    }
}
