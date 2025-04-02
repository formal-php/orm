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

/**
 * @internal
 */
final class EncodeEffect
{
    private function __construct()
    {
    }

    public function __invoke(Effect\Property $effect): Directory
    {
        // The real name is the id computed in Repository::effect()
        return Directory::named('tmp')
            ->add(
                Directory::named('properties')->add(File::named(
                    $effect->property(),
                    Content::ofString(Json::encode($effect->value())),
                )),
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
