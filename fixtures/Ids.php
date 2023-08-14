<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\Id;
use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Ids implements Comparator
{
    use Composable;

    /** @var Sequence<Id<User>> */
    private Sequence $values;

    /**
     * @param Sequence<Id<User>> $values
     */
    private function __construct(Sequence $values)
    {
        $this->values = $values;
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Id<User>> $values
     */
    public static function in(Sequence $values): self
    {
        return new self($values);
    }

    public function property(): string
    {
        return 'id';
    }

    public function sign(): Sign
    {
        return Sign::in;
    }

    /**
     * @return Sequence<Id<User>>
     */
    public function value(): Sequence
    {
        return $this->values;
    }
}
