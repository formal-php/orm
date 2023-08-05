<?php

declare(strict_types = 1);

use Formal\ORM\{
    Adapter\SQL\CreateTable,
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
            $createTable = CreateTable::of(
                Aggregates::of(Types::of(
                    Type\PointInTimeType::of(new Clock),
                )),
            );

            $queries = $createTable(User::class)
                ->map(static fn($query) => $query->sql())
                ->toList();

            $assert
                ->expected([
                    <<<SQL
                    CREATE TABLE  `user_mainAddress` (`id` varchar(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', PRIMARY KEY (`id`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_billingAddress` (`id` varchar(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', PRIMARY KEY (`id`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user` (`id` varchar(36) NOT NULL  , `createdAt` varchar(32) NOT NULL  COMMENT 'Date with timezone down to the microsecond', `name` longtext  DEFAULT NULL COMMENT 'TODO adjust the type depending on your use case', `nameStr` longtext  DEFAULT NULL COMMENT 'TODO adjust the type depending on your use case', `mainAddress` varchar(36) NOT NULL  COMMENT 'UUID', `billingAddress` varchar(36)  DEFAULT NULL COMMENT 'UUID', PRIMARY KEY (`id`), CONSTRAINT `FK_mainAddress_id` FOREIGN KEY (`mainAddress`) REFERENCES `id_mainAddress`(`id`), CONSTRAINT `FK_billingAddress_id` FOREIGN KEY (`billingAddress`) REFERENCES `id_billingAddress`(`id`))
                    SQL,
                    <<<SQL
                    CREATE TABLE  `user_addresses` (`id` varchar(36) NOT NULL  COMMENT 'UUID', `value` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', CONSTRAINT `FK_id_id` FOREIGN KEY (`id`) REFERENCES `user`(`id`))
                    SQL,
                ])
                ->same($queries);
        },
    );
};
