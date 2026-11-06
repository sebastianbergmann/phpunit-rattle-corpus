<?php declare(strict_types=1);
/*
 * This file is part of phpunit/rattle-corpus.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\RattleCorpus;

use const PHP_BINARY;
use function array_map;
use function dirname;
use function file_get_contents;
use function glob;
use function proc_close;
use function proc_open;
use function sort;
use function stream_get_contents;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;

/**
 * Runs PHPUnit with the extension, and then rattle, with the version of
 * rattle that the extension is developed with, on the properties of the
 * same test suite.
 */
#[CoversNothing]
#[Large]
final class RattleTest extends TestCase
{
    private const string PROPERTY = 'PHPUnit\RattleCorpus\Fixture\FalsifiedTest::testIsFalsifiedInTwoWays';

    public function testRattleReplaysTheStoredCounterexamplesFirstAndReportsThemAsFindings(): void
    {
        $project = FixtureProject::create(['falsified']);

        [$exit, $output] = $this->execute(
            [
                PHP_BINARY,
                'vendor/bin/phpunit',
                '--configuration',
                $project->configurationFile(),
                '--do-not-record-test-run-history',
                '--random-seed',
                '1',
            ],
        );

        $this->assertSame(2, $exit, $output);

        $stored = $this->contentsOf($project->directory() . '/.rattle.cache/' . PropertyDirectory::nameOf(self::PROPERTY) . '/corpus/*');

        $this->assertCount(2, $stored);

        // the two executions are the replays of the stored counterexamples
        [$exit, $output] = $this->execute(
            [
                PHP_BINARY,
                'vendor/bin/rattle',
                'fuzz',
                '--phpunit',
                '--configuration',
                $project->configurationFile(),
                '--source',
                'tests/fixtures/falsified',
                '--jobs',
                '1',
                '--seed',
                '1',
                '--max-executions',
                '2',
                '--cache-directory',
                $project->directory() . '/.rattle.cache',
                '--findings-directory',
                $project->directory() . '/findings',
            ],
        );

        $this->assertSame(1, $exit, $output);
        $this->assertStringContainsString('(2 stored inputs)', $output);
        $this->assertStringContainsString('Finding: LengthException at tests/fixtures/falsified/FalsifiedTest.php:31', $output);
        $this->assertStringContainsString('Finding: DomainException at tests/fixtures/falsified/FalsifiedTest.php:35', $output);
        $this->assertStringContainsString('2 new findings', $output);

        // the original input of each finding is a stored counterexample
        $this->assertSame($stored, $this->contentsOf($project->directory() . '/findings/' . PropertyDirectory::nameOf(self::PROPERTY) . '/*/original'));
    }

    /**
     * @return list<string>
     */
    private function contentsOf(string $pattern): array
    {
        $files = glob($pattern);

        $this->assertIsArray($files);

        $contents = array_map(static fn (string $file): string => (string) file_get_contents($file), $files);

        sort($contents);

        return $contents;
    }

    /**
     * @param list<string> $command
     *
     * @return array{0: int, 1: string}
     */
    private function execute(array $command): array
    {
        $pipes   = [];
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__, 2),
        );

        $this->assertIsResource($process);

        $stdout = $pipes[1] ?? null;
        $stderr = $pipes[2] ?? null;

        $this->assertIsResource($stdout);
        $this->assertIsResource($stderr);

        $output = stream_get_contents($stdout) . stream_get_contents($stderr);

        return [proc_close($process), $output];
    }
}
