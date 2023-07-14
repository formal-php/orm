<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\{
    Id,
    Definition\Template,
};

final class User
{
    /** @var Id<self> */
    #[Template(self::class)]
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
