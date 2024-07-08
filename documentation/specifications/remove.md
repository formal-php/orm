# Remove multiple aggregates

This is pretty straightforward:

```php
$orm
    ->repository(User::class)
    ->remove(SearchByName::of(Name::of('alice')));
```
