<?php
declare(strict_types = 1);

use Formal\ORM\Id;
use Innmind\BlackBox\Set;

return static function() {
    yield test(
        'Two new Ids create different values',
        static function($assert) {
            $assert
                ->expected(Id::new(stdClass::class)->toString())
                ->not()
                ->same(Id::new(stdClass::class)->toString());
        },
    )->tag(...Covers::cases());

    yield proof(
        'Id::equals()',
        given(
            Set\Uuid::any(),
            Set\Uuid::any(),
        ),
        static function($assert, $a, $b) {
            $idA = Id::for(stdClass::class)($a);
            $idABis = Id::for(stdClass::class)($a);
            $idB = Id::for(stdClass::class)($b);

            $assert->true($idA->equals($idA));
            $assert->true($idA->equals($idABis));
            $assert->false($idA->equals($idB));
        },
    )->tag(...Covers::cases());

    yield proof(
        'Id::of() throws on invalid values',
        given(Set\Strings::madeOf(Set\Unicode::any())),
        static function($assert, $value) {
            $assert->throws(static fn() => Id::of(stdClass::class, $value));
        },
    )->tag(...Covers::cases());
};
