<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Effect,
    Raw\Aggregate,
    Raw\Aggregate\Id,
    Raw\Diff,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class EncodeEffect
{
    private function __construct()
    {
    }

    /**
     * @return callable(Id): Diff
     */
    public function __invoke(Effect\Property\Collection|Effect\Entity $effect): callable
    {
        $properties = Sequence::of();
        $entities = Sequence::of();
        $optionals = Sequence::of();
        $collections = Sequence::of();

        if ($effect instanceof Effect\Entity) {
            /** @psalm-suppress MixedArgument */
            $entities = Sequence::of(Aggregate\Entity::of(
                $effect->property(),
                Sequence::of(Aggregate\Property::of(
                    $effect->effect()->property(),
                    $effect->effect()->value(),
                )),
            ));
        } else {
            /** @psalm-suppress MixedArgument */
            $properties = $effect->effects()->map(
                static fn($effect) => Aggregate\Property::of(
                    $effect->property(),
                    $effect->value(),
                ),
            );
        }

        return static fn(Id $id) => Diff::of(
            $id,
            $properties,
            $entities,
            $optionals,
            $collections,
        );
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }
}
