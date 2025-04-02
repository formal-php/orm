<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

\ini_set('memory_limit', '-1');

use Innmind\BlackBox\{
    Application,
    Runner\Load,
    Runner\CodeCoverage,
};

enum Storage
{
    case filesystem;
    case sql;
    case elasticsearch;

    public static function of(string $tag): ?self
    {
        return match ($tag) {
            'fs', 'filesystem' => self::filesystem,
            'sql' => self::sql,
            'es', 'elasticsearch' => self::elasticsearch,
            default => null,
        };
    }
}

Application::new($argv)
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
    ->parseTagWith(Storage::of(...))
    ->disableShrinking()
    ->tryToProve(Load::everythingIn(__DIR__.'/proofs/'))
    ->exit();
