--TEST--
Only the choice sequence of the minimal counterexample of a falsified property is stored when the parameter otherFalsifications is false
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\RattleCorpus\FixtureProject;

$project = FixtureProject::create(['falsified'], ['otherFalsifications' => 'false']);

var_dump($project->runPhpunit(['--no-output']));

$project->printFilesIn($project->directory() . '/.rattle.cache');
--EXPECT--
int(2)
falsifiedtest-testisfalsifiedintwoways-1239dc19/corpus/8b836469b6ec8bb562332868a2a2c01ada4622fec462a23840244d05484c0173:
choice-sequence v1
1
0
1
0
1
0
0
