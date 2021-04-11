<?php
declare(strict_types = 1);

namespace Example\Formal\ORM;

use Formal\ORM\Id;

final class User
{
    public function __construct(
        private Id $id,
        private string $username,
        private \Closure $doNotPersist,
    ) {}
}
