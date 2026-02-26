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
    SideEffect,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Transaction implements TransactionInterface
{
    private Adapter $committed;
    /** @var Sequence<callable(Adapter): Attempt<SideEffect>> */
    private Sequence $mutations;

    private function __construct(Adapter $committed)
    {
        $this->committed = $committed;
        $this->mutations = Sequence::of();
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
        $this->mutations = $this->mutations->clear();

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
            ->map(fn() => $this->mutations = $this->mutations->clear())
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
        $this->mutations = $this->mutations->clear();

        return Attempt::result($value);
    }

    /**
     * @param callable(Adapter): Attempt<SideEffect> $mutate
     *
     * @return Attempt<SideEffect>
     */
    public function mutate(callable $mutate): Attempt
    {
        $this->mutations = ($this->mutations)($mutate);

        return Attempt::result(SideEffect::identity);
    }

    public function get(Name $directory): Directory
    {
        $committed = $this
            ->committed
            ->get($directory)
            ->keep(Instance::of(Directory::class))
            ->match(
                static fn($directory) => $directory,
                static fn() => Directory::of($directory),
            );
        $notCommitted = Adapter::inMemory();
        $_ = $notCommitted
            ->add($committed)
            ->unwrap();

        $_ = $this
            ->apply($notCommitted)
            ->unwrap();

        return $notCommitted
            ->get($directory)
            ->keep(Instance::of(Directory::class))
            ->match(
                static fn($directory) => $directory,
                static fn() => Directory::of($directory),
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
            ->attempt(static fn($_, $mutate) => $mutate($adapter));
    }
}
