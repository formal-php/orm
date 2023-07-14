<?php
declare(strict_types = 1);

use Formal\ORM\{
    Manager,
    Adapter,
};
use Properties\Formal\ORM\Properties;
use Innmind\Filesystem\Adapter\InMemory;
use Innmind\BlackBox\Set;

return static function() {
    yield test(
        'Manager::repository() returns a single instance as long as it is used',
        static function($assert) {
            $manager = Manager::of(Adapter\Filesystem::of(InMemory::emulateFilesystem()));

            $repository1 = $manager->repository('a');
            $repository2 = $manager->repository('a');

            $assert->same($repository1, $repository2);
        },
    );
    yield test(
        'Manager::repository() returns an instance per class',
        static function($assert) {
            $manager = Manager::of(Adapter\Filesystem::of(InMemory::emulateFilesystem()));

            $repositoryA = $manager->repository('a');
            $repositoryB = $manager->repository('b');

            $assert
                ->expected($repositoryA)
                ->not()
                ->same($repositoryB);
        },
    );

    yield properties(
        'Filesystem properties',
        Properties::any(),
        Set\Call::of(static fn() => Manager::of(Adapter\Filesystem::of(InMemory::emulateFilesystem()))),
    );

    foreach (Properties::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set\Call::of(static fn() => Manager::of(Adapter\Filesystem::of(InMemory::emulateFilesystem()))),
        )->named('Filesystem');
    }
};
