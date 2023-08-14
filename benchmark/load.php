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
use Fixtures\Formal\ORM\User;

$os = Factory::build();
$connection = $os->remote()->sql(Url::of("mysql://root:root@127.0.0.1:3306/example"));
$aggregates = Aggregates::of(Types::of(
    Type\PointInTimeType::of($os->clock()),
));

$manager = Manager::of(
    Adapter\SQL::of($connection),
    $aggregates,
);

$_ = $manager
    ->repository(User::class)
    ->all()
    ->fetch()
    ->foreach(static fn() => null);

printf(
    "Memory: %.2f Mo\n",
    ((\memory_get_peak_usage(true) / 1024) / 1024),
);
