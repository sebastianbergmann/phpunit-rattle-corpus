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

use function basename;
use function bin2hex;
use function dirname;
use function error_clear_last;
use function error_get_last;
use function file_put_contents;
use function hash;
use function is_dir;
use function is_file;
use function mkdir;
use function preg_replace;
use function random_bytes;
use function rename;
use function sprintf;
use function unlink;
use SebastianBergmann\ChoiceSequence\ChoiceSequence;
use SebastianBergmann\ChoiceSequence\Encoder;

/**
 * The corpora of the PHPUnit properties in rattle's cache directory. The
 * corpus of a property is the directory "corpus" in the directory of the
 * property (see PropertyDirectory). Each of its files holds one choice
 * sequence in the format "choice-sequence v1", and its name is the
 * hexadecimal SHA-256 of the encoded choice sequence, as rattle names the
 * files of a corpus. A choice sequence is therefore stored only once.
 *
 * A file is written to a temporary file in the same directory first, which
 * is then renamed, so that rattle, and other PHPUnit processes that store the
 * same choice sequence at the same time, never see a file that is only
 * partly written. The name of the temporary file starts with a dot, so that
 * rattle ignores it.
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/rattle-corpus
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/rattle-corpus
 */
final readonly class Corpus
{
    private string $cacheDirectory;

    public function __construct(string $cacheDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * Stores the choice sequence in the corpus of the property, unless it is
     * stored there already, and returns the path of its file.
     *
     * @param non-empty-string       $id      the test ID of the property
     * @param list<non-negative-int> $choices
     *
     * @throws CannotWriteCorpusException
     *
     * @return non-empty-string
     */
    public function add(string $id, array $choices): string
    {
        $directory = $this->directoryOf($id);
        $encoded   = new Encoder()->encode(new ChoiceSequence($choices));
        $path      = $directory . '/' . hash('sha256', $encoded);

        if (is_file($path)) {
            return $path;
        }

        $this->createDirectory($directory);
        $this->write($path, $encoded);

        return $path;
    }

    /**
     * @param non-empty-string $id the test ID of the property
     *
     * @return non-empty-string
     */
    public function directoryOf(string $id): string
    {
        return $this->cacheDirectory . '/' . PropertyDirectory::nameOf($id) . '/corpus';
    }

    /**
     * @throws CannotWriteCorpusException
     */
    private function createDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        error_clear_last();

        // another process may have created the directory in the meantime
        if (!@mkdir($directory, 0o777, true) && !is_dir($directory)) {
            throw new CannotWriteCorpusException(
                sprintf(
                    'Directory "%s" cannot be created: %s',
                    $directory,
                    $this->lastError(),
                ),
            );
        }
    }

    /**
     * @throws CannotWriteCorpusException
     */
    private function write(string $path, string $content): void
    {
        $temporary = dirname($path) . '/.' . basename($path) . '.' . bin2hex(random_bytes(8));

        error_clear_last();

        if (@file_put_contents($temporary, $content) === false) {
            @unlink($temporary);

            throw new CannotWriteCorpusException(
                sprintf(
                    'File "%s" cannot be written: %s',
                    $path,
                    $this->lastError(),
                ),
            );
        }

        error_clear_last();

        if (!@rename($temporary, $path)) {
            // @codeCoverageIgnoreStart
            $error = $this->lastError();

            @unlink($temporary);

            throw new CannotWriteCorpusException(
                sprintf(
                    'File "%s" cannot be written: %s',
                    $path,
                    $error,
                ),
            );
            // @codeCoverageIgnoreEnd
        }
    }

    private function lastError(): string
    {
        $error = error_get_last();

        if ($error === null) {
            // @codeCoverageIgnoreStart
            return 'unknown error';
            // @codeCoverageIgnoreEnd
        }

        return (string) preg_replace('/^[a-z_]+\(\): /', '', $error['message']);
    }
}
