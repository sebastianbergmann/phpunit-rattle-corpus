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

use PHPUnit\Event\Test\PropertyFalsified;
use PHPUnit\Event\Test\PropertyFalsifiedSubscriber as PropertyFalsifiedSubscriberInterface;

/**
 * Stores the choice sequences of the counterexamples of a falsified property
 * in the corpus of the property: the choice sequence of the minimal
 * counterexample and the choice sequence of each other way in which the
 * property was falsified. A property that an explicit example falsified has
 * no choices, so nothing is stored for it.
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/rattle-corpus
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/rattle-corpus
 */
final readonly class PropertyFalsifiedSubscriber implements PropertyFalsifiedSubscriberInterface
{
    private Corpus $corpus;

    public function __construct(Corpus $corpus)
    {
        $this->corpus = $corpus;
    }

    /**
     * @throws CannotWriteCorpusException
     */
    public function notify(PropertyFalsified $event): void
    {
        $sequences = [$event->choiceSequence()];

        foreach ($event->otherFalsifications() as $otherFalsification) {
            $sequences[] = $otherFalsification['choiceSequence'];
        }

        foreach ($sequences as $choices) {
            if ($choices === []) {
                continue;
            }

            $this->corpus->add($event->test()->id(), $choices);
        }
    }
}
