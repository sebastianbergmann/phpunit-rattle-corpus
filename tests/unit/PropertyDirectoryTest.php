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

use function array_map;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Rattle\PropertyDirectory as RattlePropertyDirectory;

#[CoversClass(PropertyDirectory::class)]
#[Small]
final class PropertyDirectoryTest extends TestCase
{
    /**
     * @return non-empty-array<non-empty-string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public static function idsAndNames(): array
    {
        return [
            'test method'                     => ['Vendor\Package\ParserTest::testRoundTrip', 'parsertest-testroundtrip-dbe593bd'],
            'test method of a data set'       => ['Vendor\Package\ParserTest::testRoundTrip#empty', 'parsertest-testroundtrip-empty-f367ff76'],
            'repetition of a test method'     => ['Vendor\Package\ParserTest::testRoundTrip (repetition 1 of 2)', 'parsertest-testroundtrip-repetition-1-of-2-d1496aec'],
            'test class without a namespace'  => ['ParserTest::testRoundTrip', 'parsertest-testroundtrip-bf7df377'],
            'characters other than ASCII'     => ['Vendor\Package\ParserTest::testRoundTrip#data set with spaces and ÄÖÜ', 'parsertest-testroundtrip-data-set-with-spaces-and-09b844f0'],
            'nothing left after the last "\"' => ['Vendor\Package\\', '37dede33'],
        ];
    }

    /**
     * @return non-empty-array<non-empty-string, array{0: non-empty-string}>
     */
    public static function ids(): array
    {
        return array_map(static fn (array $idAndName): array => [$idAndName[0]], self::idsAndNames());
    }

    /**
     * @param non-empty-string $id
     */
    #[DataProvider('idsAndNames')]
    public function testIsDerivedFromTheTestIdOfTheProperty(string $id, string $name): void
    {
        $this->assertSame($name, PropertyDirectory::nameOf($id));
    }

    /**
     * The extension does not depend on rattle, so the version of rattle that
     * it is developed with tells whether rattle still derives the same names.
     *
     * @param non-empty-string $id
     */
    #[DataProvider('ids')]
    public function testIsTheNameThatRattleDerivesFromTheTestId(string $id): void
    {
        // @phpstan-ignore staticMethod.internalClass (the name that rattle derives is what the extension must derive)
        $this->assertSame(RattlePropertyDirectory::nameOf($id), PropertyDirectory::nameOf($id));
    }
}
