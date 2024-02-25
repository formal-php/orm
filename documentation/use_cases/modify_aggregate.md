# Modify an existing aggregate

Let's add a method to `User` to allow a user to change his password.

```php
use Formal\ORM\Id;

/**
 * @psalm-immutable
 */
final class User
{
    /** @var Id<self> */
    private Id $id;
    private string $username;
    private string $password;

    /**
     * @param Id<self> $id
     */
    private function __construct(Id $id, string $username, string $password)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @psalm-pure
     */
    public static function new(string $username, string $password): self
    {
        return new self(Id::new(self::class), $username, $password);
    }

    public function changePassword(string $password): self
    {
        return new self($this->id, $this->username, $password);
    }
}
```

And you call this method on a user fetched from your storage like this:

```php
$either = $manager->transactional(
    static fn() => $manager
        ->repository(User::class)
        ->get(Id::of(User::class, 'user-uuid'))
        ->map(static fn($user) => $user->changePassword('new secret password'))
        ->map($manager->repository(User::class)->put(...))
        ->either(),
);
```

This example covers 2 possibles scenarii:
- the user exists
- the user doesn't exist

If the user we try to retrieve with `->get()` exists when the `->changePassword()` is called and then the `->put()` method is called and will save our new version of the user.

If the user doesn't exist then the system won't call `->changePassword()` nor `->put()`.

An important method call is `->either()`. `->get()` returns a `Maybe` that may contain a user (as well as the 2 consecutive `->map()`) but the transaction expects an `Either` as return type, `->either()` allows to convert a `Maybe` to an `Either`.
