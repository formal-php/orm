<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\Id;
use Example\Formal\ORM\User as Model;
use Innmind\BlackBox\Set;

final class User
{
    /**
     * @return Set<Model>
     */
    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn($uuid, $username) => new Model(Id::of($uuid), $username),
            Set\Uuid::any(),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
        );
    }
}
