<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\Adapter\Transaction as TransactionInterface;
use Innmind\Filesystem\{
    Adapter,
    Directory,
    Name,
};
use Innmind\Immutable\{
    Attempt,
    Sequence,
    Map,
    Set,
    SideEffect,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Transaction implements TransactionInterface
{
    private Adapter $committed;
    private Adapter $notCommitted;
    /** @var Sequence<Directory> */
    private Sequence $mutations;
    /** @var Map<non-empty-string, Set<Name>> */
    private Map $removals;

    private function __construct(Adapter $committed)
    {
        $this->committed = $committed;
        $this->notCommitted = Adapter::inMemory();
        $this->mutations = Sequence::of();
        $this->removals = Map::of();
    }

    /**
     * @internal
     */
    public static function of(Adapter $committed): self
    {
        return new self($committed);
    }

    #[\Override]
    public function start(): Attempt
    {
        $this->reset();

        return Attempt::result(SideEffect::identity());
    }

    /**
     * @template R
     *
     * @param R $value
     *
     * @return Attempt<R>
     */
    #[\Override]
    public function commit(mixed $value): Attempt
    {
        return $this
            ->apply($this->committed)
            ->map($this->reset(...))
            ->map(static fn() => $value);
    }

    /**
     * @template R
     *
     * @param R $value
     *
     * @return Attempt<R>
     */
    #[\Override]
    public function rollback(mixed $value): Attempt
    {
        $this->reset();

        return Attempt::result($value);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function mutate(Directory $directory): Attempt
    {
        $this->mutations = ($this->mutations)($directory);
        /** @psalm-suppress InternalMethod */
        $this->removals = ($this->removals)(
            $directory->name()->toString(),
            $this
                ->removals
                ->get($directory->name()->toString())
                ->match(
                    static fn($removed) => $removed->merge($directory->removed()),
                    static fn() => $directory->removed(),
                ),
        );

        return $this->notCommitted->add($directory);
    }

    public function get(Name $directory): Directory
    {
        $notCommitted = $this
            ->notCommitted
            ->get($directory)
            ->keep(Instance::of(Directory::class))
            ->match(
                static fn($directory) => $directory,
                static fn() => Directory::of($directory),
            );
        $committed = $this
            ->committed
            ->get($directory)
            ->keep(Instance::of(Directory::class))
            ->match(
                static fn($directory) => $directory,
                static fn() => Directory::of($directory),
            );

        $merged = $this
            ->removals
            ->get($directory->toString())
            ->match(
                static fn($removed) => $removed->reduce(
                    $committed,
                    static fn(Directory $committed, $name) => $committed->remove($name),
                ),
                static fn() => $committed,
            );

        return $notCommitted->all()->reduce(
            $merged,
            static fn(Directory $merged, $file) => $merged->add($file),
        );
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function apply(Adapter $adapter): Attempt
    {
        return $this
            ->mutations
            ->sink(SideEffect::identity)
            ->attempt(static fn($_, $mutation) => $adapter->add($mutation));
    }

    private function reset(): void
    {
        $this->notCommitted = Adapter::inMemory();
        $this->mutations = $this->mutations->clear();
        $this->removals = $this->removals->clear();
    }
}
