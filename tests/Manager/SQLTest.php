<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM\Manager;

use Formal\ORM\{
    Manager\SQL,
    Manager,
    Definition\Aggregate,
    Id,
    SQL\Types,
    SQL\CreateTable,
};
use Formal\AccessLayer\{
    Connection\PDO,
    Query,
    Table,
};
use Innmind\Url\Url;
use Example\Formal\ORM\User as Model;
use Fixtures\Formal\ORM\User;
use Innmind\Immutable\Either;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class SQLTest extends TestCase
{
    use BlackBox;

    private $connection;
    private $types;
    private $aggregate;
    private $manager;

    public function setUp(): void
    {
        $port = \getenv('DB_PORT') ?: '3306';
        $this->connection = new PDO(Url::of("mysql://root:root@127.0.0.1:$port/example"));
        $this->types = new Types(...Types::default());
        $this->aggregate = Aggregate::of(Model::class)->exclude('doNotPersist');
        $this->manager = SQL::of(
            $this->connection,
            $this->types,
            $this->aggregate,
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Manager::class, $this->manager);
    }

    public function testAddingOutsideATransactionThrows()
    {
        $this
            ->forAll(User::any())
            ->then(function($user) {
                $repository = $this->manager->repository(Model::class);

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
                $this->reset();

                $repository = $this->manager->repository(Model::class);
                $expected = Either::right($return);

                $this->assertEquals(
                    $expected,
                    $this->manager->transactional(static function() use ($repository, $user, $expected) {
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
                $repository = $this->manager->repository(Model::class);
                $this->assertEquals(
                    $either,
                    $this->manager->transactional(static fn() => $either)
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
                $this->reset();

                $repository = $this->manager->repository(Model::class);
                $expected = $this->createMock(\Throwable::class);

                try {
                    $this->manager->transactional(static function() use ($repository, $user, $expected) {
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
                $this->reset();

                $repository = $this->manager->repository(Model::class);
                $expected = Either::left($this->createMock(\Throwable::class));

                $this->assertEquals(
                    $expected,
                    $this->manager->transactional(static function() use ($repository, $user, $expected) {
                        $repository->add($user);

                        return $expected;
                    }),
                );

                $this->assertCount(0, $repository->all());
            });
    }

    private function reset(): void
    {
        ($this->connection)(Query\DropTable::ifExists(new Table\Name($this->aggregate->name())));
        $create = new CreateTable($this->types);
        ($this->connection)($create($this->aggregate));
    }
}
