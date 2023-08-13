<?php
declare(strict_types = 1);

use Formal\ORM\{
    Manager,
    Adapter,
    Definition\Aggregates,
    Definition\Types,
    Definition\Type,
};
use Fixtures\Formal\ORM\{
    User,
    Random,
};
use Properties\Formal\ORM\Properties;
use Formal\AccessLayer\{
    Connection\PDO,
    Query\DropTable,
    Table,
};
use Innmind\Filesystem\Adapter\InMemory;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Url\Url;
use Innmind\BlackBox\Set;

return static function() {
    yield test(
        'Manager::repository() returns a single instance as long as it is used',
        static function($assert) {
            $manager = Manager::of(Adapter\Filesystem::of(InMemory::emulateFilesystem()));

            $repository1 = $manager->repository(User::class);
            $repository2 = $manager->repository(User::class);

            $assert->same($repository1, $repository2);
        },
    );
    yield test(
        'Manager::repository() returns an instance per class',
        static function($assert) {
            $manager = Manager::of(Adapter\Filesystem::of(InMemory::emulateFilesystem()));
            $repositoryA = $manager->repository(User::class);
            $repositoryB = $manager->repository(Random::class);

            $assert
                ->expected($repositoryA)
                ->not()
                ->same($repositoryB);
        },
    );

    yield properties(
        'Filesystem properties',
        Properties::any(),
        Set\Call::of(static fn() => Manager::of(
            Adapter\Filesystem::of(InMemory::emulateFilesystem()),
            Aggregates::of(Types::of(
                Type\PointInTimeType::of(new Clock),
            )),
        )),
    );

    foreach (Properties::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set\Call::of(static fn() => Manager::of(
                Adapter\Filesystem::of(InMemory::emulateFilesystem()),
                Aggregates::of(Types::of(
                    Type\PointInTimeType::of(new Clock),
                )),
            )),
        )->named('Filesystem');
    }

    $port = \getenv('DB_PORT') ?: '3306';
    $connection = PDO::of(Url::of("mysql://root:root@127.0.0.1:$port/example"));

    yield properties(
        'SQL properties',
        Properties::any(),
        Set\Call::of(static function() use ($connection) {
            $aggregates = Aggregates::of(Types::of(
                Type\PointInTimeType::of(new Clock),
            ));
            $connection(DropTable::ifExists(Table\Name::of('user_addresses')));
            $connection(DropTable::ifExists(Table\Name::of('user')));
            $connection(DropTable::ifExists(Table\Name::of('user_mainAddress')));
            $connection(DropTable::ifExists(Table\Name::of('user_billingAddress')));
            $_ = Adapter\SQL\CreateTable::of($aggregates)(User::class)->foreach($connection);

            return Manager::of(
                Adapter\SQL::of($connection),
                $aggregates,
            );
        }),
    );

    foreach (Properties::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set\Call::of(static function() use ($connection) {
                $aggregates = Aggregates::of(Types::of(
                    Type\PointInTimeType::of(new Clock),
                ));
                $connection(DropTable::ifExists(Table\Name::of('user_addresses')));
                $connection(DropTable::ifExists(Table\Name::of('user')));
                $connection(DropTable::ifExists(Table\Name::of('user_mainAddress')));
                $connection(DropTable::ifExists(Table\Name::of('user_billingAddress')));
                $_ = Adapter\SQL\CreateTable::of($aggregates)(User::class)->foreach($connection);

                return Manager::of(
                    Adapter\SQL::of($connection),
                    $aggregates,
                );
            }),
        )->named('SQL');
    }
};
