<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\Id;

final class Random
{
    /** @var Id<self> */
    private Id $id;

    private function __construct()
    {
        $this->id = Id::new(self::class);
    }

    public static function new(): self
    {
        return new self;
    }

    public function id(): Id
    {
        return $this->id;
    }
}
