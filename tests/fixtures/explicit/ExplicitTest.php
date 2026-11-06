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

use PHPUnit\Framework\Attributes\Property;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ExplicitTest extends TestCase
{
    /**
     * The explicit example falsifies the property before an example is
     * generated, and it has no choices.
     *
     * @param int<0, 9> $value
     */
    #[Property]
    #[TestWith([10])]
    public function testIsFalsifiedByAnExplicitExample(int $value): void
    {
        $this->assertLessThan(10, $value);
    }
}
