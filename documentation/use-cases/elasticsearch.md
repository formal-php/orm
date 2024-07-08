# Search an aggregate via Elasticsearch

If you have multiple aggregates (let's say `Product` and `Vendor`) that you want to query in a single specification, you'll need to create a third aggregate (ie `Search\Product`) that will denormalize the data of both aggregates.

These aggregates may look like this:

=== "Product"
    ```php title="Product.php"
    use Formal\ORM\Id;

    final readonly class Product
    {
        /** @var Id<self> */
        private Id $id;
        private string $name;
        /** @var Id<Vendor> */
        private Id $vendor;

        /**
         * @param Id<Vendor> $vendor
         */
        public function __construct(string $name, Id $vendor)
        {
            $this->id = Id::new(self::class);
            $this->name = $name;
            $this->vendor = $vendor;
        }

        // Getters not displayed for conciseness
    }
    ```

=== "Vendor"
    ```php title="Vendor.php"
    use Formal\ORM\Id;

    final readonly class Vendor
    {
        /** @var Id<self> */
        private Id $id;
        private string $name;

        public function __construct(string $name)
        {
            $this->id = Id::new(self::class);
            $this->name = $name;
        }

        // Getters not displayed for conciseness
    }
    ```

=== "Search product"
    ```php title="Search/Product.php"
    namespace Search;

    use Formal\ORM\Id;

    final readonly class Product
    {
        /** @var Id<self> */
        private Id $id;
        /** @var Id<\Product> */
        private Id $productId;
        private string $productName;
        /** @var Id<\Vendor> */
        private Id $vendorId;
        private string $vendorName;

        public function __construct(\Product $product, \Vendor $vendor)
        {
            $this->id = Id::new(self::class);
            $this->productId = $product->id();
            $this->productName = $product->name();
            $this->vendorId = $vendor->id();
            $this->vendorName = $vendor->name();
        }
    }
    ```

And to persist this new aggregate you would use 2 instances of the ORM like this:

```php
use Formal\ORM\{
    Manager,
    Adapter\Elasticsearch,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;
use Innmind\Immutable\Either;

$os = Factory::build();
$sql = Manager::sql(
    $os->remote()->sql(
        Url::of('mysql://user:password@host:3306/database?charset=utf8mb4'),
    ),
);
$elasticsearch = Manager::of(
    Elasticsearch::of($os->remote()->http()),
);

$vendors = $sql->repository(Vendor::class);
$searchableProducts = $sql
    ->repository(Product::class)
    ->all()
    ->flatMap(
        static fn($product) => $vendors
            ->get($product->vendor())
            ->map(static fn($vendor) => new Search\Product(
                $product,
                $vendor,
            ))
            ->toSequence(), //(1)
    );

$repository = $elasticsearch->repository(Search\Product::class);
$elasticsearch->transactional(
    static function() use ($repository, $searchableProducts) {
        $_ = $searchableProducts->foreach($repository->put(...));

        return Either::right(null);
    },
);
```

1. If the vendor doesn't exist the product is discarded.

Then you can search these new aggregates as [any other](../specifications/index.md).
