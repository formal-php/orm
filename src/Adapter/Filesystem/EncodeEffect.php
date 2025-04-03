<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\Effect;
use Innmind\Filesystem\{
    Directory,
    File,
    File\Content,
    Name,
};
use Innmind\Json\Json;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class EncodeEffect
{
    private function __construct()
    {
    }

    public function __invoke(Effect\Property|Effect\Entity|Effect\Collection $effect): Directory
    {
        if ($effect instanceof Effect\Entity) {
            return Directory::named('tmp')->add(
                Directory::named('entities')->add(
                    Directory::named($effect->property())->add(
                        File::named(
                            $effect->effect()->property(),
                            Content::ofString(Json::encode($effect->effect()->value())),
                        ),
                    ),
                ),
            );
        }

        if ($effect instanceof Effect\Property) {
            $effects = Sequence::of($effect);
        } else {
            $effects = $effect->effects();
        }

        // The real name is the id computed in Repository::effect()
        return Directory::named('tmp')
            ->add(
                Directory::of(
                    Name::of('properties'),
                    $effects->map(static fn($effect) => File::named(
                        $effect->property(),
                        Content::ofString(Json::encode($effect->value())),
                    )),
                ),
            );
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }
}
