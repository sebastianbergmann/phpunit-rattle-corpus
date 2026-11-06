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
use function sprintf;
use function str_starts_with;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Runner\Extension\Extension as ExtensionInterface;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Copies the choice sequences of the counterexamples of falsified properties
 * into the corpora of the properties in rattle's cache directory, from which
 * "rattle fuzz --phpunit" starts.
 *
 * The extension has two parameters: "cacheDirectory", the cache directory of
 * rattle, ".rattle.cache" by default, and "otherFalsifications", "true" by
 * default, "false" to store only the choice sequence of the minimal
 * counterexample of a property, not those of the other ways in which it was
 * falsified. A relative cache directory is relative to the directory of the
 * configuration file of PHPUnit, or to the working directory when none is
 * used. An invalid parameter is reported as a PHPUnit warning, and nothing is
 * stored then.
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

        if ($cacheDirectory === '') {
            $this->warn(sprintf('The parameter "cacheDirectory" of %s must not be empty', self::class));

            return;
        }

        $otherFalsifications = true;

        if ($parameters->has('otherFalsifications')) {
            $value = $parameters->get('otherFalsifications');

            if ($value !== 'true' && $value !== 'false') {
                $this->warn(sprintf('The parameter "otherFalsifications" of %s must be "true" or "false", not "%s"', self::class, $value));

                return;
            }

            $otherFalsifications = $value === 'true';
        }

        $facade->registerSubscriber(
            new PropertyFalsifiedSubscriber(
                new Corpus($this->resolve($cacheDirectory, $configuration)),
                $otherFalsifications,
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

    /**
     * @param non-empty-string $message
     */
    private function warn(string $message): void
    {
        EventFacade::emitter()->testRunnerTriggeredPhpunitWarning($message);
    }
}
