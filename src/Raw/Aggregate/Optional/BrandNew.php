<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate\Optional;

use Formal\ORM\Raw\Aggregate\{
    Optional,
    Property,
};
use Innmind\Immutable\{
    Maybe,
    Set,
};

/**
 * This indicates in a Diff that there was previously no value for the
 * corresponding property
 *
 * @psalm-immutable
 */
final class BrandNew
{
    private Optional $optional;

    private function __construct(Optional $optional)
    {
        $this->optional = $optional;
    }

    /**
     * @psalm-pure
     */
    public static function of(Optional $optional): self
    {
        return new self($optional);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->optional->name();
    }

    /**
     * @return Maybe<Set<Property>>
     */
    public function properties(): Maybe
    {
        return $this->optional->properties();
    }
}
