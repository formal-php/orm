<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM;

use Formal\ORM\Id;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class IdTest extends TestCase
{
    use BlackBox;

    public function testTwoNewIdsAreNotEqual()
    {
        $this->assertNotSame(Id::new(), Id::new());
        $this->assertNotSame(Id::new()->toString(), Id::new()->toString());
        $this->assertFalse(Id::new()->equals(Id::new()));
    }

    public function testAcceptAnyUuid()
    {
        $this
            ->forAll(Set\Uuid::any())
            ->then(function($uuid) {
                $id = Id::of($uuid);

                $this->assertInstanceOf(Id::class, $id);
                $this->assertSame($uuid, $id->toString());
            });
    }

    public function testDoesntAcceptRandomStrings()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string) {
                try {
                    Id::of($string);
                    $this->fail('it should throw');
                } catch (\LogicException $e) {
                    $this->assertSame("Invalid id '$string'", $e->getMessage());
                }
            });
    }

    public function testEquality()
    {
        $this
            ->forAll(Set\Uuid::any(), Set\Uuid::any())
            ->filter(static fn($uuid1, $uuid2) => $uuid1 !== $uuid2)
            ->then(function($uuid1, $uuid2) {
                $id1 = Id::of($uuid1);
                $id2 = Id::of($uuid2);

                $this->assertTrue($id1->equals($id1));
                $this->assertTrue($id1->equals(Id::of($uuid1)));
                $this->assertTrue(Id::of($uuid1)->equals($id1));
                $this->assertFalse($id1->equals($id2));
                $this->assertFalse($id2->equals($id1));
            });
    }
}
