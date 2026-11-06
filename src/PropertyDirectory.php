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

use function hash;
use function preg_replace;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

/**
 * The name of the directory of a PHPUnit property in rattle's cache
 * directory, which rattle derives from the test ID of the property: the part
 * of the test ID after its last backslash, with every run of characters
 * other than letters and digits replaced with a hyphen, in lowercase and
 * without hyphens at its start and end, followed by a hyphen and the first
 * eight hexadecimal digits of the SHA-256 of the test ID. When nothing is
 * left of the part of the test ID, the name is these eight digits alone.
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/rattle-corpus
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/rattle-corpus
 */
final readonly class PropertyDirectory
{
    /**
     * @param non-empty-string $id the test ID of the property
     *
     * @return non-empty-string
     */
    public static function nameOf(string $id): string
    {
        $separator = strrpos($id, '\\');
        $name      = $separator === false ? $id : substr($id, $separator + 1);
        $name      = trim(strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name)), '-');

        return ($name === '' ? '' : $name . '-') . substr(hash('sha256', $id), 0, 8);
    }
}
