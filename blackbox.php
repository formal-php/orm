<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

\ini_set('memory_limit', '-1');

use Innmind\BlackBox\{
    Application,
    Runner\Load,
    Runner\CodeCoverage,
};

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
    ->disableShrinking()
    ->tryToProve(Load::everythingIn(__DIR__.'/proofs/'))
    ->exit();
