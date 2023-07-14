<?php
declare(strict_types = 1);

use Formal\ORM\Manager;

return static function() {
    yield test(
        'Manager::repository() returns a single instance as long as it is used',
        static function($assert) {
            $manager = Manager::of();

            $repository1 = $manager->repository('a');
            $repository2 = $manager->repository('a');

            $assert->same($repository1, $repository2);
        },
    );
    yield test(
        'Manager::repository() returns an instance per class',
        static function($assert) {
            $manager = Manager::of();

            $repositoryA = $manager->repository('a');
            $repositoryB = $manager->repository('b');

            $assert
                ->expected($repositoryA)
                ->not()
                ->same($repositoryB);
        },
    );
};
