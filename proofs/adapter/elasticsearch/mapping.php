<?php

declare(strict_types = 1);

use Formal\ORM\{
    Adapter\Elasticsearch\Mapping,
    Definition\Aggregates,
    Definition\Type,
    Definition\Types,
};
use Innmind\TimeContinuum\{
    Earth\Clock,
    PointInTime,
};
use Fixtures\Formal\ORM\{
    User,
    CreatedAtType,
};

return static function() {
    yield test(
        'Define Elasticsearch index mapping for the User fixture',
        static function($assert) {
            $aggregates = Aggregates::of(Types::of(
                Type\Support::class(
                    PointInTime::class,
                    Type\PointInTimeType::new(new Clock),
                ),
                CreatedAtType::of(...),
            ));

            $mapping = Mapping::new()($aggregates->get(User::class));

            $assert
                ->expected([
                    'properties' => [
                        'id' => [
                            'type' => 'keyword',
                        ],
                        'createdAt' => [
                            'type' => 'text',
                        ],
                        'wrappedCreatedAt' => [
                            'type' => 'double',
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
                                'id' => [
                                    'type' => 'long',
                                ],
                                'enabled' => [
                                    'type' => 'boolean',
                                ],
                            ],
                        ],
                        'billingAddress' => [
                            'properties' => [
                                'value' => [
                                    'type' => 'text',
                                ],
                                'id' => [
                                    'type' => 'long',
                                ],
                                'enabled' => [
                                    'type' => 'boolean',
                                ],
                            ],
                        ],
                        'sibling' => [
                            'properties' => [
                                'id' => [
                                    'type' => 'keyword',
                                ],
                            ],
                        ],
                        'addresses' => [
                            'type' => 'nested',
                            'properties' => [
                                'value' => [
                                    'type' => 'text',
                                ],
                                'id' => [
                                    'type' => 'long',
                                ],
                                'enabled' => [
                                    'type' => 'boolean',
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
    )->tag(Storage::elasticsearch);
};
