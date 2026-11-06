<?php declare(strict_types=1);
/*
 * This file is part of phpunit/rattle-corpus.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\RattleCorpus\Fixture;

use function count;
use function in_array;
use DomainException;
use LengthException;
use PHPUnit\Framework\Attributes\Property;
use PHPUnit\Framework\TestCase;

final class FalsifiedTest extends TestCase
{
    /**
     * A list of three or more values falsifies the property, and so does a
     * shorter list that contains a 9, which shrinking finds on the way.
     *
     * @param list<int<0, 9>> $values
     */
    #[Property]
    public function testIsFalsifiedInTwoWays(array $values): void
    {
        if (count($values) >= 3) {
            throw new LengthException('too long');
        }

        if (in_array(9, $values, true)) {
            throw new DomainException('contains 9');
        }

        $this->assertLessThan(3, count($values));
    }
}
