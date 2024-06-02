# Persist my first aggregate

Before trying to persist anything you must first define your aggregate. For this example will use a user containing a username and a password.

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
}
```

The annotations in this example are not necessary but allows the static analyzer [`vimeo/psalm`](https://packagist.org/packages/vimeo/psalm) to make sure your aggregate is indeed immutable.

The `id` property is a required one for a class to be considered an aggregate and must be typed with `Formal\ORM\Id`.

!!! warning ""
    This example doesn't hash the password for simplicity's sake. You shouldn't store password in clear text!

Then to persist a new instance of this aggregate:

```php
use Formal\ORM\Manager;
use Innmind\OperatingSystem\Factory;
use Innmind\Immutable\Either;

$os = Factory::build();
$tmp = $os->filesystem()->mount($os->status()->tmp());
$manager = Manager::filesystem($tmp);

$either = $manager->transactional(
    static fn() => Either::right(
        $manager
            ->repository(User::class)
            ->put(User::new('Jane', 'Doe')),
    ),
);
```

This example creates an ORM that will persist data in tmp folder of your operating system. `$manager->transactional()` opens a transaction then call the function you passed, in this case it creates a `Jane` user, and since the function returns an `Either::right()` it commits the addition to the filesystem and then `transactional` return the value returned by the function.

If instead of returning `Either::right()` you return `Either::left()` any data created or modified you have been rolledback. In this case it would mean `Jane` would not exist outside of the transaction.
