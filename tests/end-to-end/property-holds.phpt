--TEST--
Nothing is stored when no property is falsified
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\RattleCorpus\FixtureProject;

$project = FixtureProject::create(['holds']);

var_dump($project->runPhpunit(['--no-output']));

$project->printFilesIn($project->directory() . '/.rattle.cache');
--EXPECT--
int(0)
The directory does not exist
