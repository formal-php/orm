<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM\Repository;

use Formal\ORM\{
    Repository\InMemory,
    Repository,
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
        $this->assertInstanceOf(
            Repository::class,
            new InMemory(User::class, static fn() => true),
        );
    }

    public function testThrowsWhenTryingToAddWhenNotInTransaction()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $repository = new InMemory(User::class, static fn() => false);

                try {
                    $repository->add(new User(Id::of($uuid), $username));
                    $this->fail('it should throw');
                } catch (\LogicException $e) {
                    $this->assertSame(
                        'Trying to mutate the repository outside of a transaction',
                        $e->getMessage(),
                    );
                    $this->assertCount(0, $repository->all());
                }
            });
    }

    public function testAdd()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $repository = new InMemory(User::class, static fn() => true);
                $user = new User(Id::of($uuid), $username);

                $this->assertCount(0, $repository->all());
                $this->assertNull($repository->add($user));
                $this->assertSame(
                    $user,
                    $repository->get(Id::of($uuid))->match(
                        static fn($entity) => $entity,
                        static fn() => null,
                    ),
                );
                $this->assertCount(1, $repository->all());
            });
    }

    public function testThrowsWhenTryingToRemoveWhenNotInTransaction()
    {
        $this
            ->forAll(Set\Uuid::any())
            ->then(function($uuid) {
                $repository = new InMemory(User::class, static fn() => false);

                try {
                    $repository->remove(Id::of($uuid));
                    $this->fail('it should throw');
                } catch (\LogicException $e) {
                    $this->assertSame(
                        'Trying to mutate the repository outside of a transaction',
                        $e->getMessage(),
                    );
                    $this->assertCount(0, $repository->all());
                }
            });
    }

    public function testRemove()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $repository = new InMemory(User::class, static fn() => true);
                $id = Id::of($uuid);
                $user = new User($id, $username);

                $this->assertCount(0, $repository->all());
                $this->assertNull($repository->add($user));
                $this->assertNull($repository->remove($id));
                $this->assertFalse(
                    $repository->get(Id::of($uuid))->match(
                        static fn() => true,
                        static fn() => false,
                    ),
                );
                $this->assertCount(0, $repository->all());
            });
    }

    public function testRemovingUnknownIdHasNoEffect()
    {
        $this
            ->forAll(Set\Uuid::any())
            ->then(function($uuid) {
                $repository = new InMemory(User::class, static fn() => true);

                $this->assertNull($repository->remove(Id::of($uuid)));
                $this->assertFalse(
                    $repository->get(Id::of($uuid))->match(
                        static fn() => true,
                        static fn() => false,
                    ),
                );
                $this->assertCount(0, $repository->all());
            });
    }

    public function testRollbackAdd()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $repository = new InMemory(User::class, static fn() => true);
                $user = new User(Id::of($uuid), $username);

                $this->assertCount(0, $repository->all());
                $repository->add($user);
                $this->assertNull($repository->rollback());
                $this->assertFalse(
                    $repository->get(Id::of($uuid))->match(
                        static fn() => true,
                        static fn() => false,
                    ),
                );
                $this->assertCount(0, $repository->all());
            });
    }

    public function testCommittedAddCantBeRollbacked()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $repository = new InMemory(User::class, static fn() => true);
                $user = new User(Id::of($uuid), $username);

                $this->assertCount(0, $repository->all());
                $repository->add($user);
                $this->assertNull($repository->commit());
                $this->assertNull($repository->rollback());
                $this->assertSame(
                    $user,
                    $repository->get(Id::of($uuid))->match(
                        static fn($entity) => $entity,
                        static fn() => null,
                    ),
                );
                $this->assertCount(1, $repository->all());
            });
    }

    public function testRollbackRemove()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $repository = new InMemory(User::class, static fn() => true);
                $id = Id::of($uuid);
                $user = new User($id, $username);

                $this->assertCount(0, $repository->all());
                $repository->add($user);
                $this->assertNull($repository->commit());
                $repository->remove($id);
                $this->assertCount(0, $repository->all());
                $this->assertNull($repository->rollback());
                $this->assertTrue(
                    $repository->get(Id::of($uuid))->match(
                        static fn() => true,
                        static fn() => false,
                    ),
                );
                $this->assertCount(1, $repository->all());
            });
    }

    public function testCommittedRemoveCantBeRollbacked()
    {
        $this
            ->forAll(
                Set\Uuid::any(),
                Set\Strings::any(),
            )
            ->then(function($uuid, $username) {
                $repository = new InMemory(User::class, static fn() => true);
                $id = Id::of($uuid);
                $user = new User($id, $username);

                $this->assertCount(0, $repository->all());
                $repository->add($user);
                $this->assertNull($repository->commit());
                $repository->remove($id);
                $this->assertNull($repository->commit());
                $this->assertCount(0, $repository->all());
                $this->assertNull($repository->rollback());
                $this->assertFalse(
                    $repository->get(Id::of($uuid))->match(
                        static fn() => true,
                        static fn() => false,
                    ),
                );
                $this->assertCount(0, $repository->all());
            });
    }
}
