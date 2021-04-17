<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Repository,
    Id,
    Definition\Aggregate,
    SQL\Types,
    SQL\MatchId,
    SQL\Table\Normalize,
};
use Formal\AccessLayer\{
    Connection,
    Row,
    Query,
    Table,
};
use Innmind\Reflection\{
    ReflectionClass,
    InjectionStrategy,
    Instanciator,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Maybe,
    Set,
};

/**
 * @template V of object
 * @implements Repository<V>
 */
final class SQL implements Repository
{
    /** @var class-string<V> */
    private string $class;
    private Aggregate $aggregate;
    private Connection $connection;
    private Types $types;
    private Normalize $normalize;
    /** @var callable(): bool */
    private $allowMutation;

    /**
     * @param class-string<V> $class
     * @param callable(): bool $allowMutation
     */
    public function __construct(
        string $class,
        Aggregate $aggregate,
        Connection $connection,
        Types $types,
        callable $allowMutation
    ) {
        $this->class = $class;
        $this->aggregate = $aggregate;
        $this->connection = $connection;
        $this->types = $types;
        $this->normalize = new Normalize($aggregate, $types);
        $this->allowMutation = $allowMutation;
    }

    public function get(Id $id): Maybe
    {
        $select = $this->select()->where($this->match($id));
        $aggregates = ($this->connection)($select)
            ->mapTo($this->class, fn($row) => $this->denormalize($row));

        if (!$aggregates->empty()) {
            return Maybe::just($aggregates->first());
        }

        /** @var Maybe<V> */
        return Maybe::nothing();
    }

    public function add(object $aggregate): void
    {
        // todo handle updates
        $this->assertMutable();

        ($this->connection)(new Query\Insert(
            new Table\Name($this->aggregate->name()),
            Row::of(($this->normalize)($aggregate)),
        ));
    }

    public function remove(Id $id): void
    {
        $this->assertMutable();

        ($this->connection)(
            (new Query\Delete(new Table\Name($this->aggregate->name())))
                ->where($this->match($id)),
        );
    }

    public function all(): Set
    {
        return ($this->connection)($this->select())
            ->mapTo(
                $this->class,
                fn($row) => $this->denormalize($row),
            )
            ->toSetOf($this->class);
    }

    public function matching(Specification $specification): Set
    {
        return Set::of($this->class);
    }

    /**
     * @throws \LogicException
     */
    private function assertMutable(): void
    {
        if (!($this->allowMutation)()) {
            throw new \LogicException('Trying to mutate the repository outside of a transaction');
        }
    }

    /**
     * @return V
     */
    private function denormalize(Row $row): object
    {
        $reflection = ReflectionClass::of(
            $this->class,
            null,
            new InjectionStrategy\ReflectionStrategy,
            new Instanciator\ConstructorLessInstanciator,
        );

        /** @var V */
        return $this
            ->aggregate
            ->properties()
            ->reduce(
                $reflection,
                fn(ReflectionClass $reflection, $property): ReflectionClass => $reflection->withProperty(
                    $property->name(),
                    ($this->types)($property)->denormalize(
                        $row->column($property->name()),
                    ),
                ),
            )
            ->build();
    }

    private function select(): Query\Select
    {
        return new Query\Select(new Table\Name($this->aggregate->name()));
    }

    private function match(Id $id): Specification
    {
        return new MatchId(
            $this->aggregate->id()->property(),
            $id,
        );
    }
}
