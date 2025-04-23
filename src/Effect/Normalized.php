<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\Effect\Normalized\{
    Properties,
    Entity,
    Child,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Normalized
{
    private function __construct(
        private Properties|Entity|Child\Add $effect,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function of(Properties|Entity|Child\Add $effect): self
    {
        return new self($effect);
    }

    public function unwrap(): Properties|Entity|Child\Add
    {
        return $this->effect;
    }
}
