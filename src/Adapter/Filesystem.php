<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
};
use Innmind\Filesystem\Adapter as Storage;

final class Filesystem implements Adapter
{
    private Storage $adapter;

    private function __construct(Storage $adapter)
    {
        $this->adapter = $adapter;
    }

    public static function of(Storage $adapter): self
    {
        return new self($adapter);
    }

    public function repository(Aggregate $definition): Repository
    {
        return Filesystem\Repository::of($this->adapter, $definition);
    }
}
