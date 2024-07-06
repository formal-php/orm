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
                    CREATE TABLE  `user` (`id` char(36) NOT NULL  COMMENT 'UUID', `createdAt` varchar(32) NOT NULL  COMMENT 'Date with timezone down to the microsecond', `name` longtext  DEFAULT NULL COMMENT 'TODO adjust the type depending on your use case', `nameStr` longtext  DEFAULT NULL COMMENT 'TODO adjust the type depending on your use case', `role` longtext  DEFAULT NULL COMMENT 'TODO adjust the type depending on your use case', PRIMARY KEY (`id`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_mainAddress` (`aggregateId` char(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', `id` bigint  DEFAULT NULL COMMENT 'TODO Adjust the size depending on your use case', `enabled` tinyint(1) NOT NULL  COMMENT 'Boolean', CONSTRAINT `FK_user_mainAddress` FOREIGN KEY (`aggregateId`) REFERENCES `user`(`id`) ON DELETE CASCADE, UNIQUE (`aggregateId`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_billingAddress` (`aggregateId` char(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', `id` bigint  DEFAULT NULL COMMENT 'TODO Adjust the size depending on your use case', `enabled` tinyint(1) NOT NULL  COMMENT 'Boolean', CONSTRAINT `FK_user_billingAddress` FOREIGN KEY (`aggregateId`) REFERENCES `user`(`id`) ON DELETE CASCADE, UNIQUE (`aggregateId`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_addresses` (`aggregateId` char(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', `id` bigint  DEFAULT NULL COMMENT 'TODO Adjust the size depending on your use case', `enabled` tinyint(1) NOT NULL  COMMENT 'Boolean', CONSTRAINT `FK_user_addresses` FOREIGN KEY (`aggregateId`) REFERENCES `user`(`id`) ON DELETE CASCADE)
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_roles` (`aggregateId` char(36) NOT NULL  COMMENT 'UUID', `name` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', CONSTRAINT `FK_user_roles` FOREIGN KEY (`aggregateId`) REFERENCES `user`(`id`) ON DELETE CASCADE)
                    SQL,
                ])
                ->same($queries);
        },
    );
};
