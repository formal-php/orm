<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Specification\{
    Entity,
    Child,
};
use Innmind\Specification\{
    Specification,
    Composite,
    Operator,
    Not,
    Comparator,
    Sign,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Query
{
    private function __construct()
    {
    }

    public function __invoke(Specification $specification): array
    {
        if ($specification instanceof Entity) {
            return $this->visit($specification, $specification->entity().'.');
        }

        if ($specification instanceof Child) {
            return [
                'nested' => $specification->collection(),
                'query' => $this->visit(
                    $specification->specification(),
                    $specification->collection().'.data.',
                ),
            ];
        }

        return $this->visit($specification);
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }

    private function visit(Specification $specification, string $prefix = ''): array
    {
        if ($specification instanceof Composite) {
            $left = $this->visit($specification->left(), $prefix);
            $right = $this->visit($specification->right(), $prefix);

            return match ($specification->operator()) {
                Operator::and => $this->and($left, $right),
                Operator::or => $this->or($left, $right),
            };
        }

        if ($specification instanceof Not) {
            return $this->not($this->visit($specification->specification(), $prefix));
        }

        if ($specification instanceof Comparator) {
            return $this->compare($specification, $prefix);
        }

        $class = $specification::class;

        throw new \LogicException("Unsupported specification '$class'");
    }

    private function and(array $left, array $right): array
    {
        return [
            'bool' => [
                'must' => [$left, $right],
            ],
        ];
    }

    private function or(array $left, array $right): array
    {
        return [
            'bool' => [
                'should' => [$left, $right],
            ],
        ];
    }

    private function not(array $query): array
    {
        return [
            'bool' => [
                'must_not' => [$query],
            ],
        ];
    }

    private function compare(Comparator $specification, string $prefix): array
    {
        $property = $prefix.$specification->property();

        // TODO adapt (in)equality and in search type based on the property type
        // being a keyword or not from the mapping
        return match ($specification->sign()) {
            Sign::equality => [
                'match_phrase' => [
                    $property => [
                        'query' => $specification->value(),
                    ],
                ],
            ],
            Sign::inequality => [
                'bool' => [
                    'must_not' => [[
                        'match_phrase' => [
                            $property => [
                                'query' => $specification->value(),
                            ],
                        ],
                    ]],
                ],
            ],
            Sign::lessThan => [
                'range' => [
                    $property => [
                        'lt' => $specification->value(),
                    ],
                ],
            ],
            Sign::moreThan => [
                'range' => [
                    $property => [
                        'gt' => $specification->value(),
                    ],
                ],
            ],
            Sign::lessThanOrEqual => [
                'range' => [
                    $property => [
                        'lte' => $specification->value(),
                    ],
                ],
            ],
            Sign::moreThanOrEqual => [
                'range' => [
                    $property => [
                        'gte' => $specification->value(),
                    ],
                ],
            ],
            Sign::isNull => [
                'bool' => [
                    'must_not' => [[
                        'exists' => [
                            'field' => $property,
                        ],
                    ]],
                ],
            ],
            Sign::isNotNull => [
                'exists' => [
                    'field' => $property,
                ],
            ],
            Sign::startsWith => [
                'wildcard' => [
                    $property => [
                        'value' => "{$specification->value()}*",
                        'case_insensitive' => true, // to match the SQL behaviour
                    ],
                ],
            ],
            Sign::endsWith => [
                'wildcard' => [
                    $property => [
                        'value' => "*{$specification->value()}",
                        'case_insensitive' => true, // to match the SQL behaviour
                    ],
                ],
            ],
            Sign::contains => [
                'match_phrase_prefix' => [
                    $property => [
                        'query' => $specification->value(),
                    ],
                ],
            ],
            Sign::in => [
                'terms' => [
                    $property => $specification->value(),
                ],
            ],
        };
    }
}
