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
use PHPUnit\Framework\Attributes\Property;
use PHPUnit\Framework\TestCase;

final class HoldsTest extends TestCase
{
    /**
     * @param list<int<0, 9>> $values
     */
    #[Property]
    public function testHolds(array $values): void
    {
        $this->assertGreaterThanOrEqual(0, count($values));
    }
}
