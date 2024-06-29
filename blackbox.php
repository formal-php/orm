<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

\ini_set('memory_limit', '-1');

use Innmind\BlackBox\{
    Application,
    Runner\Load,
    Runner\CodeCoverage,
};

enum Covers {
    case all;
    case filesystem;
    case sql;
    case elasticsearch;

    public static function from(string $case): ?self
    {
        return match ($case) {
            'all' => self::all,
            'filesystem' => self::filesystem,
            'sql' => self::sql,
            'elasticsearch' => self::elasticsearch,
        };
    }
}

Application::new($argv)
    ->parseTagWith(Covers::from(...))
    ->codeCoverage(
        CodeCoverage::of(
            __DIR__.'/src/',
            __DIR__.'/proofs/',
            __DIR__.'/fixtures/',
        )
            ->dumpTo('coverage.clover')
            ->enableWhen(\getenv('ENABLE_COVERAGE') !== false),
    )
    ->scenariiPerProof(match (\getenv('ENABLE_COVERAGE')) {
        false => 100,
        default => 1,
    })
    ->disableShrinking()
    ->tryToProve(Load::everythingIn(__DIR__.'/proofs/'))
    ->exit();
