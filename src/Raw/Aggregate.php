<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\Set;

final class Aggregate
{
    private function __construct()
    {
    }

    public function id(): Aggregate\Id
    {
        return Aggregate\Id::of();
    }

    /**
     * @return Set<Aggregate\Property>
     */
    public function properties(): Set
    {
        return Set::of();
    }
}
