<?php

declare(strict_types = 1);

use Formal\ORM\{
    Adapter\SQL\ShowCreateTable,
    Definition\Aggregates,
    Definition\Type,
    Definition\Types,
};
use Innmind\TimeContinuum\Earth\Clock;
use Fixtures\Formal\ORM\User;

return static function() {
    yield test(
        'Create table for the User fixture',
        static function($assert) {
            $show = ShowCreateTable::of(
                Aggregates::of(Types::of(
                    Type\PointInTimeType::of(new Clock),
                )),
            );

            $queries = $show(User::class)
                ->map(static fn($query) => $query->sql())
                ->toList();

            $assert
                ->expected([
                    <<<SQL
                    CREATE TABLE  `user` (`id` varchar(36) NOT NULL  COMMENT 'UUID', `createdAt` varchar(32) NOT NULL  COMMENT 'Date with timezone down to the microsecond', `name` longtext  DEFAULT NULL COMMENT 'TODO adjust the type depending on your use case', `nameStr` longtext  DEFAULT NULL COMMENT 'TODO adjust the type depending on your use case', PRIMARY KEY (`id`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_mainAddress` (`id` varchar(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', CONSTRAINT `FK_user_mainAddress` FOREIGN KEY (`id`) REFERENCES `user`(`id`) ON DELETE CASCADE, UNIQUE (`id`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_billingAddress` (`id` varchar(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', CONSTRAINT `FK_user_billingAddress` FOREIGN KEY (`id`) REFERENCES `user`(`id`) ON DELETE CASCADE, UNIQUE (`id`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_addresses` (`entityReference` varchar(36) NOT NULL  COMMENT 'UUID', `id` varchar(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', PRIMARY KEY (`entityReference`), CONSTRAINT `FK_user_addresses` FOREIGN KEY (`id`) REFERENCES `user`(`id`) ON DELETE CASCADE)
                    SQL,
                ])
                ->same($queries);
        },
    );
};
