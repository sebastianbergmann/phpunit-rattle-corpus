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

use function dirname;
use function getcwd;
use function preg_match;
use function str_starts_with;
use PHPUnit\Runner\Extension\Extension as ExtensionInterface;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Copies the choice sequences of the counterexamples of falsified properties
 * into the corpora of the properties in rattle's cache directory, from which
 * "rattle fuzz --phpunit" starts.
 *
 * The parameter "cacheDirectory" is the cache directory of rattle,
 * ".rattle.cache" by default. A relative cache directory is relative to the
 * directory of the configuration file of PHPUnit, or to the working directory
 * when none is used.
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/rattle-corpus
 */
final readonly class Extension implements ExtensionInterface
{
    private const string DEFAULT_CACHE_DIRECTORY = '.rattle.cache';

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $cacheDirectory = self::DEFAULT_CACHE_DIRECTORY;

        if ($parameters->has('cacheDirectory')) {
            $cacheDirectory = $parameters->get('cacheDirectory');
        }

        $facade->registerSubscriber(
            new PropertyFalsifiedSubscriber(
                new Corpus($this->resolve($cacheDirectory, $configuration)),
            ),
        );
    }

    private function resolve(string $directory, Configuration $configuration): string
    {
        if (str_starts_with($directory, '/') || str_starts_with($directory, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $directory) === 1) {
            return $directory;
        }

        if ($configuration->hasConfigurationFile()) {
            return dirname($configuration->configurationFile()) . '/' . $directory;
        }

        $workingDirectory = getcwd();

        if ($workingDirectory === false) {
            // @codeCoverageIgnoreStart
            return $directory;
            // @codeCoverageIgnoreEnd
        }

        return $workingDirectory . '/' . $directory;
    }
}
