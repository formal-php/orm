<?php
declare(strict_types = 1);

use Formal\ORM\Id;
use Innmind\BlackBox\Set;

return static function() {
    yield test(
        'Two new Ids create different values',
        static function($assert) {
            $assert
                ->expected(Id::new(\stdClass::class)->toString())
                ->not()
                ->same(Id::new(\stdClass::class)->toString());
        },
    );

    yield proof(
        'Id::equals()',
        given(
            Set\Uuid::any(),
            Set\Uuid::any(),
        ),
        static function($assert, $a, $b) {
            $idA = Id::of(\stdClass::class, $a);
            $idABis = Id::of(\stdClass::class, $a);
            $idB = Id::of(\stdClass::class, $b);

            $assert->true($idA->equals($idA));
            $assert->true($idA->equals($idABis));
            $assert->false($idA->equals($idB));
        },
    );
};
