<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\Set;

final class Aggregate
{
    private function __construct()
    {
    }

    public static function of(): self
    {
        return new self;
    }

    public function id(): Aggregate\Id
    {
        return Aggregate\Id::of('todo', 'todo');
    }

    /**
     * @return Set<Aggregate\Property>
     */
    public function properties(): Set
    {
        return Set::of();
    }
}
