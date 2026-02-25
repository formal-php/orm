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

    private function __construct(Adapter $committed)
    {
        $this->committed = $committed;
        $this->notCommitted = $this->reset();
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
        $this->notCommitted = $this->reset();

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
            ->notCommitted
            ->root()
            ->all()
            ->sink(SideEffect::identity())
            ->attempt(fn($_, $file) => $this->committed->add($file))
            ->map(fn() => $this->notCommitted = $this->reset())
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
        $this->notCommitted = $this->reset();

        return Attempt::result($value);
    }

    /**
     * @param callable(Adapter): void $mutate
     */
    public function mutate(callable $mutate): void
    {
        $mutate($this->notCommitted);
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

        /** @psalm-suppress InternalMethod */
        $merged = $notCommitted->removed()->reduce(
            $committed,
            static fn(Directory $committed, $name) => $committed->remove($name),
        );

        return $notCommitted->all()->reduce(
            $merged,
            static fn(Directory $merged, $file) => $merged->add($file),
        );
    }

    private function reset(): Adapter
    {
        return Adapter::inMemory();
    }
}
