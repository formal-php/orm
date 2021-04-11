<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM\Definition;

use Formal\ORM\Definition\Aggregate;
use Example\Formal\ORM\User;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class AggregateTest extends TestCase
{
    use BlackBox;

    public function testProperties()
    {
        $aggregate1 = Aggregate::of(User::class);
        $aggregate2 = $aggregate1->exclude('doNotPersist');

        $this->assertNotSame($aggregate1, $aggregate2);

        $properties1 = $aggregate1->properties();
        $properties2 = $aggregate2->properties();

        $this->assertCount(3, $properties1);
        $this->assertTrue($properties1->any(static fn($property) => $property->name() === 'id'));
        $this->assertTrue($properties1->any(static fn($property) => $property->name() === 'username'));
        $this->assertTrue($properties1->any(static fn($property) => $property->name() === 'doNotPersist'));

        $properties2 = $aggregate2->properties();

        $this->assertCount(2, $properties2);
        $this->assertTrue($properties2->any(static fn($property) => $property->name() === 'id'));
        $this->assertTrue($properties2->any(static fn($property) => $property->name() === 'username'));
    }

    public function testId()
    {
        $aggregate = Aggregate::of(User::class);

        $this->assertSame('id', $aggregate->id()->property());
    }

    public function testThrowWhenNoIdDefined()
    {
        $aggregate = Aggregate::of('stdClass');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No id property defined for 'stdClass'");

        $aggregate->id();
    }

    public function testNameUseClassNameByDefault()
    {
        $this->assertSame(
            'user',
            Aggregate::of(User::class)->name(),
        );
    }

    public function testUseProvidedName()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($name) {
                $aggregate1 = Aggregate::of(User::class);
                $aggregate2 = $aggregate1->referenceAs($name);

                $this->assertNotSame($aggregate1, $aggregate2);
                $this->assertSame('user', $aggregate1->name());
                $this->assertSame($name, $aggregate2->name());
            });
    }
}
