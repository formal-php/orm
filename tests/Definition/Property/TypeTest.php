<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM\Definition\Property;

use Formal\ORM\{
    Definition\Property\Type,
    Id,
};
use Example\Formal\ORM\User;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class TypeTest extends TestCase
{
    use BlackBox;

    public function testOfClass()
    {
        $type = Type::of(User::class, 'id');

        $this->assertTrue($type->ofClass(Id::class));
        $this->assertFalse($type->ofClass('stdClass'));

        $type = Type::of(User::class, 'username');

        $this->assertFalse($type->ofClass(Id::class));
        $this->assertFalse($type->ofClass('string'));
    }
}
