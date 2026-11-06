--TEST--
Nothing is stored for a property that an explicit example falsified, as it has no choices
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\RattleCorpus\FixtureProject;

$project = FixtureProject::create(['explicit']);

var_dump($project->runPhpunit(['--no-output']));

$project->printFilesIn($project->directory() . '/.rattle.cache');
--EXPECT--
int(1)
The directory does not exist
