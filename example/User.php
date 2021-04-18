<?php
declare(strict_types = 1);

namespace Example\Formal\ORM;

use Formal\ORM\Id;

final class User
{
    public function __construct(
        private Id $id,
        private string $username,
        private ?\Closure $doNotPersist = null,
    ) {}

    public function uuid(): string
    {
        return $this->id->toString();
    }

    public function username(): string
    {
        return $this->username;
    }

    public function rename(string $username): self
    {
        return new self($this->id, $username);
    }

    public function equals(self $user): bool
    {
        return $this->id->equals($user->id) &&
            $this->username === $user->username;
    }
}
