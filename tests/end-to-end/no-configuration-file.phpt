--TEST--
A relative cache directory is relative to the working directory when no configuration file is used
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\RattleCorpus\Extension;
use PHPUnit\RattleCorpus\FixtureProject;
use PHPUnit\TextUI\Application;

$project = FixtureProject::create([]);

chdir($project->directory());

var_dump(
    new Application()->run(
        [
            'phpunit',
            '--no-configuration',
            '--extension',
            Extension::class,
            '--do-not-record-test-run-history',
            '--random-seed',
            '1',
            '--no-output',
            FixtureProject::fixture('falsified'),
        ],
    ),
);

$project->printFilesIn($project->directory() . '/.rattle.cache');
--EXPECT--
int(2)
falsifiedtest-testisfalsifiedintwoways-1239dc19/corpus/2ff4bb1ad2a4d2743455da56feab90e0fa99e1a4f1d2d4dc80f5bdb7ee8b8d69:
choice-sequence v1
1
8
1
9
0
falsifiedtest-testisfalsifiedintwoways-1239dc19/corpus/8b836469b6ec8bb562332868a2a2c01ada4622fec462a23840244d05484c0173:
choice-sequence v1
1
0
1
0
1
0
0
