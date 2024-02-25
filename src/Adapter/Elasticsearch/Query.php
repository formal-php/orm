<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Specification\Entity2,
    Specification\Child,
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
    /** @var array<non-empty-string, bool> */
    private array $isText = [];

    private function __construct(Mapping $mapping, Definition $definition)
    {
        /** @var array<non-empty-string, array> */
        $properties = $mapping($definition)['properties'] ?? [];

        foreach ($properties as $property => $config) {
            // Properties
            if (\array_key_exists('type', $config) && $config['type'] !== 'nested') {
                $this->isText[$property] = $config['type'] === 'text';

                continue;
            }

            // Entities and optionals
            if (
                \array_key_exists('properties', $config) &&
                \is_array($config['properties']) &&
                !\array_key_exists('type', $config)
            ) {
                /** @var array $entityPropertyConfig */
                foreach ($config['properties'] as $entityProperty => $entityPropertyConfig) {
                    if (\array_key_exists('type', $entityPropertyConfig)) {
                        $this->isText["$property.$entityProperty"] = $entityPropertyConfig['type'] === 'text';

                        continue;
                    }
                }
            }

            // Collections
            if (
                \array_key_exists('properties', $config) &&
                \is_array($config['properties']) &&
                \array_key_exists('type', $config)
            ) {
                /**
                 * @psalm-suppress ImpureMethodCall
                 * @var string $collectionProperty
                 * @var array $collectionPropertyConfig
                 */
                foreach ($config['properties']['data']['properties'] ?? [] as $collectionProperty => $collectionPropertyConfig) {
                    if (\array_key_exists('type', $collectionPropertyConfig)) {
                        $this->isText["$property.data.$collectionProperty"] = $collectionPropertyConfig['type'] === 'text';

                        continue;
                    }
                }
            }
        }
    }

    public function __invoke(Specification $specification): array
    {
        if ($specification instanceof Entity2) {
            return $this->visit($specification->specification(), $specification->entity().'.');
        }

        if ($specification instanceof Child) {
            return [
                'nested' => [
                    'path' => $specification->collection(),
                    'query' => $this->visit(
                        $specification->specification(),
                        $specification->collection().'.data.',
                    ),
                ],
            ];
        }

        return $this->visit($specification);
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function new(Mapping $mapping, Definition $definition): self
    {
        return new self($mapping, $definition);
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

        if ($this->isText[$property] ?? true) {
            return $this->compareText($property, $specification);
        }

        return $this->compareKeyword($property, $specification);
    }

    private function compareKeyword(string $property, Comparator $specification): array
    {
        return match ($specification->sign()) {
            Sign::equality => [
                'term' => [
                    $property => [
                        'value' => $specification->value(),
                    ],
                ],
            ],
            Sign::inequality => [
                'bool' => [
                    'must_not' => [[
                        'term' => [
                            $property => [
                                'value' => $specification->value(),
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
            Sign::startsWith => $this->compareText($property, $specification),
            Sign::endsWith => $this->compareText($property, $specification),
            Sign::contains => $this->compareText($property, $specification), // Type can only be text as arrays can't be persisted
            Sign::in => [
                'terms' => [
                    $property => $specification->value(),
                ],
            ],
        };
    }

    private function compareText(string $property, Comparator $specification): array
    {
        /** @psalm-suppress MixedArgument Due to the array map */
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
            Sign::lessThan => $this->compareKeyword($property, $specification),
            Sign::moreThan => $this->compareKeyword($property, $specification),
            Sign::lessThanOrEqual => $this->compareKeyword($property, $specification),
            Sign::moreThanOrEqual => $this->compareKeyword($property, $specification),
            Sign::isNull => $this->compareKeyword($property, $specification),
            Sign::isNotNull => $this->compareKeyword($property, $specification),
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
                'bool' => [
                    'should' => \array_map(
                        static fn(string|int|bool|null $value) => [
                            'match_phrase' => [
                                $property => [
                                    'query' => $value,
                                ],
                            ],
                        ],
                        $specification->value(),
                    ),
                ],
            ],
        };
    }
}
