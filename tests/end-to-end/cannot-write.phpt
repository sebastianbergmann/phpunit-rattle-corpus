--TEST--
A corpus that cannot be written is reported once as a PHPUnit warning, for all properties, and the exit code of PHPUnit is the one of the falsified properties
--FILE--
<?php declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\RattleCorpus\FixtureProject;

$project = FixtureProject::create(['falsified', 'also-falsified'], ['cacheDirectory' => 'file']);

file_put_contents($project->directory() . '/file', '');

var_dump($project->runPhpunit(['--no-progress']));
--EXPECTF--
PHPUnit %s by Sebastian Bergmann and contributors.

Runtime:       PHP %s
Configuration: %s/phpunit.xml
Random Seed:   1

Time: %s, Memory: %s

There was 1 PHPUnit test runner warning:

1) The counterexamples of PHPUnit\RattleCorpus\Fixture\%s cannot be stored in the corpus of rattle: Directory "%s/file/%s/corpus" cannot be created: Not a directory

--
%A
ERRORS!
Tests: 2, Assertions: %d, Errors: 1, Failures: 1, PHPUnit Warnings: 1.
int(2)
