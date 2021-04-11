<?php
declare(strict_types = 1);

namespace Tests\Formal\ORM\SQL;

use Formal\ORM\{
    SQL\CreateTable,
    SQL\Types,
    Definition\Aggregate,
};
use Example\Formal\ORM\User;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class CreateTableTest extends TestCase
{
    use BlackBox;

    public function testSqlForExampleUser()
    {
        $createTable = new CreateTable(new Types(...Types::default()));

        $query = $createTable(
            Aggregate::of(User::class)
                ->exclude('doNotPersist'),
        );

        $this->assertSame(
            'CREATE TABLE  `user` (`id` char(36) NOT NULL  , `username` text NOT NULL  , PRIMARY KEY (`id`))',
            $query->sql(),
        );
    }
}
