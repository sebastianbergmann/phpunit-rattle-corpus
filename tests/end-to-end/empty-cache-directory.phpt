--TEST--
An empty cache directory is reported as a PHPUnit warning, and nothing is stored
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\RattleCorpus\FixtureProject;

$project = FixtureProject::create(['falsified'], ['cacheDirectory' => '']);

var_dump($project->runPhpunit(['--no-progress']));

$project->printFilesIn($project->directory() . '/.rattle.cache');
--EXPECTF--
PHPUnit %s by Sebastian Bergmann and contributors.

Runtime:       PHP %s
Configuration: %s/phpunit.xml
Random Seed:   1

Time: %s, Memory: %s

There was 1 PHPUnit test runner warning:

1) The parameter "cacheDirectory" of PHPUnit\RattleCorpus\Extension must not be empty

--
%A
ERRORS!
Tests: 1, Assertions: %d, Errors: 1, PHPUnit Warnings: 1.
int(2)
The directory does not exist
