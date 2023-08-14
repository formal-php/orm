<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

use Formal\ORM\{
    Definition\Aggregates,
    Definition\Types,
    Definition\Type,
    Manager,
    Adapter,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;
use Innmind\BlackBox\{
    Set,
    Random,
};
use Innmind\Immutable\Either;
use Fixtures\Formal\ORM\User;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

$os = Factory::build();
$connection = $os->remote()->sql(Url::of("mysql://root:root@127.0.0.1:3306/example"));
$aggregates = Aggregates::of(Types::of(
    Type\PointInTimeType::of($os->clock()),
));

$_ = Adapter\SQL\ShowCreateTable::of($aggregates)(User::class)->foreach($connection);

$manager = Manager::of(
    Adapter\SQL::of($connection),
    $aggregates,
);
$repository = $manager->repository(User::class);

$users = Set\Composite::immutable(
    User::new(...),
    PointInTime::any(),
    Set\Strings::madeOf(Set\Chars::alphanumerical())->between(0, 250),
);
$users = Set\Randomize::of($users)->take(100_000)->values(Random::default);

$manager->transactional(function() use ($repository, $users) {
    foreach ($users as $user) {
        $repository->put($user->unwrap());
    }

    return Either::right(null);
});
