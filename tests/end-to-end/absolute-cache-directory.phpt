--TEST--
An absolute cache directory is used as it is
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\RattleCorpus\FixtureProject;

$elsewhere = FixtureProject::create([]);
$project   = FixtureProject::create(['falsified'], ['cacheDirectory' => $elsewhere->directory() . '/cache']);

var_dump($project->runPhpunit(['--no-output']));

$elsewhere->printFilesIn($elsewhere->directory() . '/cache');
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
The directory does not exist
