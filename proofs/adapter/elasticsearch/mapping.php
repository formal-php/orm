<?php

declare(strict_types = 1);

use Formal\ORM\{
    Adapter\Elasticsearch\Mapping,
    Definition\Aggregates,
    Definition\Type,
    Definition\Types,
};
use Innmind\TimeContinuum\Earth\Clock;
use Fixtures\Formal\ORM\User;

return static function() {
    yield test(
        'Define Elasticsearch index mapping for the User fixture',
        static function($assert) {
            $aggregates = Aggregates::of(Types::of(
                Type\PointInTimeType::of(new Clock),
            ));

            $mapping = Mapping::new()($aggregates->get(User::class));

            $assert
                ->expected([
                    'properties' => [
                        'id' => [
                            'type' => 'keyword',
                            'index' => false,
                        ],
                        'createdAt' => [
                            'type' => 'date_nanos',
                        ],
                        'name' => [
                            'type' => 'text',
                        ],
                        'nameStr' => [
                            'type' => 'text',
                        ],
                        'role' => [
                            'type' => 'text',
                        ],
                        'mainAddress' => [
                            'properties' => [
                                'value' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'billingAddress' => [
                            'properties' => [
                                'value' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'addresses' => [
                            'type' => 'nested',
                            'properties' => [
                                'value' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'roles' => [
                            'type' => 'nested',
                            'properties' => [
                                'name' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ])
                ->same($mapping);
        },
    );
};
