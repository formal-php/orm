<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\Id;

final class Sibling
{
    /** @var Id<User> */
    private Id $id;

    /**
     * @param Id<User> $id
     */
    private function __construct(Id $id)
    {
        $this->id = $id;
    }

    /**
     * @param Id<User> $id
     */
    public static function of(Id $id): self
    {
        return new self($id);
    }
}
