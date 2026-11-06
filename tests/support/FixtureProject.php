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

use const ENT_QUOTES;
use const ENT_XML1;
use const PHP_EOL;
use function bin2hex;
use function chmod;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function htmlspecialchars;
use function is_dir;
use function is_link;
use function mkdir;
use function random_bytes;
use function register_shutdown_function;
use function rmdir;
use function scandir;
use function sort;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;
use PHPUnit\TextUI\Application;

/**
 * A temporary directory with a configuration file of PHPUnit that registers
 * the extension, with the given parameters, for the tests of the given
 * fixtures. The directory is removed when the process ends.
 */
final readonly class FixtureProject
{
    private string $directory;

    /**
     * @param list<non-empty-string>          $fixtures   the names of directories in tests/fixtures
     * @param array<non-empty-string, string> $parameters the parameters of the extension
     */
    public static function create(array $fixtures, array $parameters = []): self
    {
        $directory = sys_get_temp_dir() . '/phpunit-rattle-corpus-' . bin2hex(random_bytes(8));

        mkdir($directory);

        register_shutdown_function(static function () use ($directory): void
        {
            self::remove($directory);
        });

        $project = new self($directory);

        file_put_contents($project->configurationFile(), self::configuration($fixtures, $parameters));

        return $project;
    }

    /**
     * @return non-empty-string
     */
    public static function fixture(string $name): string
    {
        return dirname(__DIR__) . '/fixtures/' . $name;
    }

    private function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function configurationFile(): string
    {
        return $this->directory . '/phpunit.xml';
    }

    /**
     * Runs PHPUnit in this process with the configuration file, and the same
     * seed every time, and returns its exit code. PHPUnit can only be run
     * once in a process.
     *
     * @param list<string> $arguments
     */
    public function runPhpunit(array $arguments = []): int
    {
        return new Application()->run(
            [
                'phpunit',
                '--configuration',
                $this->configurationFile(),
                '--do-not-record-test-run-history',
                '--random-seed',
                '1',
                ...$arguments,
            ],
        );
    }

    /**
     * Prints the path of every file below the directory, relative to it, and
     * the content of the file, or that there is no such directory.
     */
    public function printFilesIn(string $directory): void
    {
        if (!is_dir($directory)) {
            print 'The directory does not exist' . PHP_EOL;

            return;
        }

        $files = $this->filesIn($directory, '');

        if ($files === []) {
            print 'There are no files in the directory' . PHP_EOL;

            return;
        }

        foreach ($files as $file) {
            print $file . ':' . PHP_EOL . file_get_contents($directory . '/' . $file);
        }
    }

    /**
     * @return list<string>
     */
    private function filesIn(string $directory, string $prefix): array
    {
        $names = scandir($directory);
        $files = [];

        foreach ($names === false ? [] : $names as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            if (is_dir($directory . '/' . $name)) {
                foreach ($this->filesIn($directory . '/' . $name, $prefix . $name . '/') as $file) {
                    $files[] = $file;
                }

                continue;
            }

            $files[] = $prefix . $name;
        }

        sort($files);

        return $files;
    }

    /**
     * @param list<non-empty-string>          $fixtures
     * @param array<non-empty-string, string> $parameters
     */
    private static function configuration(array $fixtures, array $parameters): string
    {
        $directories = '';

        foreach ($fixtures as $fixture) {
            $directories .= sprintf('            <directory>%s</directory>' . "\n", self::escape(self::fixture($fixture)));
        }

        $bootstrap = '        <bootstrap class="' . self::escape(Extension::class) . '"/>' . "\n";

        if ($parameters !== []) {
            $bootstrap = '        <bootstrap class="' . self::escape(Extension::class) . '">' . "\n";

            foreach ($parameters as $name => $value) {
                $bootstrap .= sprintf('            <parameter name="%s" value="%s"/>' . "\n", self::escape($name), self::escape($value));
            }

            $bootstrap .= '        </bootstrap>' . "\n";
        }

        $schema = self::escape(dirname(__DIR__, 2) . '/vendor/phpunit/phpunit/phpunit.xsd');

        return <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="{$schema}">
    <testsuites>
        <testsuite name="fixtures">
{$directories}        </testsuite>
    </testsuites>

    <extensions>
{$bootstrap}    </extensions>
</phpunit>

EOT;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
    }

    private static function remove(string $path): void
    {
        if (!is_dir($path) || is_link($path)) {
            @unlink($path);

            return;
        }

        @chmod($path, 0o777);

        $names = scandir($path);

        foreach ($names === false ? [] : $names as $name) {
            if ($name !== '.' && $name !== '..') {
                self::remove($path . '/' . $name);
            }
        }

        @rmdir($path);
    }
}
