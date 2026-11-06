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

use function bin2hex;
use function chmod;
use function file_get_contents;
use function file_put_contents;
use function hash;
use function is_dir;
use function is_writable;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\ChoiceSequence\Decoder;

#[CoversClass(Corpus::class)]
#[UsesClass(PropertyDirectory::class)]
#[Medium]
final class CorpusTest extends TestCase
{
    private const string PROPERTY = 'Vendor\Package\ParserTest::testRoundTrip';
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/phpunit-rattle-corpus-' . bin2hex(random_bytes(8));

        mkdir($this->cacheDirectory);
    }

    protected function tearDown(): void
    {
        $this->remove($this->cacheDirectory);
    }

    public function testStoresAChoiceSequenceInTheCorpusOfTheProperty(): void
    {
        $path = new Corpus($this->cacheDirectory)->add(self::PROPERTY, [1, 7, 0]);

        $this->assertSame($this->cacheDirectory . '/parsertest-testroundtrip-dbe593bd/corpus/' . hash('sha256', "choice-sequence v1\n1\n7\n0\n"), $path);
        $this->assertStringEqualsFile($path, "choice-sequence v1\n1\n7\n0\n");
        $this->assertSame([1, 7, 0], new Decoder()->decode((string) file_get_contents($path))->choices());
    }

    public function testStoresAChoiceSequenceOnlyOnce(): void
    {
        $corpus = new Corpus($this->cacheDirectory);

        $first  = $corpus->add(self::PROPERTY, [1, 7, 0]);
        $second = $corpus->add(self::PROPERTY, [1, 7, 0]);

        $this->assertSame($first, $second);
        $this->assertSame([hash('sha256', "choice-sequence v1\n1\n7\n0\n")], $this->filesIn($corpus->directoryOf(self::PROPERTY)));
    }

    public function testStoresDifferentChoiceSequencesInDifferentFiles(): void
    {
        $corpus = new Corpus($this->cacheDirectory);

        $corpus->add(self::PROPERTY, [1, 7, 0]);
        $corpus->add(self::PROPERTY, [0]);

        $this->assertCount(2, $this->filesIn($corpus->directoryOf(self::PROPERTY)));
    }

    public function testLeavesNoTemporaryFileBehind(): void
    {
        $corpus = new Corpus($this->cacheDirectory);

        $corpus->add(self::PROPERTY, [1, 7, 0]);

        $this->assertSame([hash('sha256', "choice-sequence v1\n1\n7\n0\n")], $this->filesIn($corpus->directoryOf(self::PROPERTY), true));
    }

    public function testRaisesAnExceptionWhenTheDirectoryOfTheCorpusCannotBeCreated(): void
    {
        file_put_contents($this->cacheDirectory . '/file', '');

        $this->expectException(CannotWriteCorpusException::class);
        $this->expectExceptionMessageIsOrContains('Directory "' . $this->cacheDirectory . '/file/parsertest-testroundtrip-dbe593bd/corpus" cannot be created: Not a directory');

        new Corpus($this->cacheDirectory . '/file')->add(self::PROPERTY, [1, 7, 0]);
    }

    public function testRaisesAnExceptionWhenTheFileOfAChoiceSequenceCannotBeWritten(): void
    {
        $corpus = new Corpus($this->cacheDirectory);

        mkdir($corpus->directoryOf(self::PROPERTY), 0o777, true);
        chmod($corpus->directoryOf(self::PROPERTY), 0o555);

        if (is_writable($corpus->directoryOf(self::PROPERTY))) {
            $this->markTestSkipped('The directory of the corpus cannot be made read-only');
        }

        $this->expectException(CannotWriteCorpusException::class);
        $this->expectExceptionMessageIsOrContains('File "' . $corpus->directoryOf(self::PROPERTY) . '/' . hash('sha256', "choice-sequence v1\n1\n7\n0\n") . '" cannot be written: ');

        $corpus->add(self::PROPERTY, [1, 7, 0]);
    }

    /**
     * @return list<string>
     */
    private function filesIn(string $directory, bool $includeHiddenFiles = false): array
    {
        $names = scandir($directory);

        $this->assertIsArray($names);

        $files = [];

        foreach ($names as $name) {
            if ($name === '.' || $name === '..' || (!$includeHiddenFiles && $name[0] === '.')) {
                continue;
            }

            $files[] = $name;
        }

        return $files;
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            @unlink($path);

            return;
        }

        chmod($path, 0o777);

        $names = scandir($path);

        foreach ($names === false ? [] : $names as $name) {
            if ($name !== '.' && $name !== '..') {
                $this->remove($path . '/' . $name);
            }
        }

        rmdir($path);
    }
}
