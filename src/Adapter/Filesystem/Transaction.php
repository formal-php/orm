<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\Adapter\Transaction as TransactionInterface;
use Innmind\Filesystem\{
    Adapter,
    Directory,
    Name,
};
use Innmind\Immutable\Predicate\Instance;

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

    public function start(): void
    {
        $this->notCommitted = $this->reset();
    }

    /**
     * @template R
     *
     * @return callable(R): R
     */
    public function commit(): callable
    {
        return function(mixed $value) {
            $this->notCommitted->root()->files()->foreach(
                fn($file) => $this->committed->add($file),
            );
            $this->notCommitted = $this->reset();

            return $value;
        };
    }

    /**
     * @template R
     *
     * @return callable(R): R
     */
    public function rollback(): callable
    {
        return function(mixed $value) {
            $this->notCommitted = $this->reset();

            return $value;
        };
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
                static fn() => Directory\Directory::of($directory),
            );
        $committed = $this
            ->committed
            ->get($directory)
            ->keep(Instance::of(Directory::class))
            ->match(
                static fn($directory) => $directory,
                static fn() => Directory\Directory::of($directory),
            );

        $merged = $notCommitted->removed()->reduce(
            $committed,
            static fn(Directory $committed, $name) => $committed->remove($name),
        );

        return $notCommitted->files()->reduce(
            $merged,
            static fn(Directory $merged, $file) => $merged->add($file),
        );
    }

    private function reset(): Adapter
    {
        return Adapter\InMemory::emulateFilesystem();
    }
}
