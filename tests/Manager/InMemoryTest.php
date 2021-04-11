<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM\Manager;

use Formal\ORM\{
    Manager\InMemory,
    Manager,
    Id,
};
use Example\Formal\ORM\User;
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
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $manager = new InMemory;
                $repository = $manager->repository(User::class);

                try {
                    $repository->add(new User(Id::of($uuid), $username));
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
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $manager = new InMemory;
                $repository = $manager->repository(User::class);

                $this->assertNull($manager->transactional(static fn() => $repository->add(
                    new User(Id::of($uuid), $username),
                )));
                $this->assertCount(1, $repository->all());
            });
    }

    public function testRepositoryNoLongerMutableAfterTransaction()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $manager = new InMemory;
                $repository = $manager->repository(User::class);
                $this->assertNull($manager->transactional(static fn() => null));

                try {
                    $repository->add(new User(Id::of($uuid), $username));
                    $this->fail('it should throw');
                } catch (\Throwable $e) {
                    $this->assertInstanceOf(\LogicException::class, $e);
                }
            });
    }

    public function testRollbackWhenAnExceptionIsThrown()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $manager = new InMemory;
                $repository = $manager->repository(User::class);
                $expected = $this->createMock(\Throwable::class);
                $user = new User(Id::of($uuid), $username);

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
}
