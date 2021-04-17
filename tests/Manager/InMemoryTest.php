<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM\Manager;

use Formal\ORM\{
    Manager\InMemory,
    Manager,
    Id,
};
use Example\Formal\ORM\User as Model;
use Fixtures\Formal\ORM\User;
use Innmind\Immutable\Either;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class InMemoryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Manager::class, new InMemory);
    }

    public function testAddingOutsideATransactionThrows()
    {
        $this
            ->forAll(User::any())
            ->then(function($user) {
                $manager = new InMemory;
                $repository = $manager->repository(Model::class);

                try {
                    $repository->add($user);
                    $this->fail('it should throw');
                } catch (\Throwable $e) {
                    $this->assertInstanceOf(\LogicException::class, $e);
                }
            });
    }

    public function testRepositoryLoadedOutsideOfTransactionAcceptMutationWhenModifiedInsideTransaction()
    {
        $this
            ->forAll(
                User::any(),
                Set\AnyType::any(),
            )
            ->then(function($user, $return) {
                $manager = new InMemory;
                $repository = $manager->repository(Model::class);
                $expected = Either::right($return);

                $this->assertEquals(
                    $expected,
                    $manager->transactional(static function() use ($repository, $user, $expected) {
                        $repository->add($user);

                        return $expected;
                    }),
                );
                $this->assertCount(1, $repository->all());
            });
    }

    public function testRepositoryNoLongerMutableAfterTransaction()
    {
        $this
            ->forAll(
                User::any(),
                new Set\Either(
                    Set\Decorate::immutable(
                        static fn($value) => Either::right($value),
                        Set\AnyType::any(),
                    ),
                    Set\Elements::of(Either::left($this->createMock(\Throwable::class))),
                ),
            )
            ->then(function($user, $either) {
                $manager = new InMemory;
                $repository = $manager->repository(Model::class);
                $this->assertEquals(
                    $either,
                    $manager->transactional(static fn() => $either)
                );

                try {
                    $repository->add($user);
                    $this->fail('it should throw');
                } catch (\Throwable $e) {
                    $this->assertInstanceOf(\LogicException::class, $e);
                }
            });
    }

    public function testRollbackWhenAnExceptionIsThrown()
    {
        $this
            ->forAll(User::any())
            ->then(function($user) {
                $manager = new InMemory;
                $repository = $manager->repository(Model::class);
                $expected = $this->createMock(\Throwable::class);

                try {
                    $manager->transactional(static function() use ($repository, $user, $expected) {
                        $repository->add($user);

                        throw $expected;
                    });
                    $this->fail('it should throw');
                } catch (\Throwable $e) {
                    $this->assertSame($expected, $e);
                }

                $this->assertCount(0, $repository->all());
            });
    }

    public function testRollbackWhenALeftValueIsReturned()
    {
        $this
            ->forAll(User::any())
            ->then(function($user) {
                $manager = new InMemory;
                $repository = $manager->repository(Model::class);
                $expected = Either::left($this->createMock(\Throwable::class));

                $this->assertEquals(
                    $expected,
                    $manager->transactional(static function() use ($repository, $user, $expected) {
                        $repository->add($user);

                        return $expected;
                    }),
                );

                $this->assertCount(0, $repository->all());
            });
    }
}
