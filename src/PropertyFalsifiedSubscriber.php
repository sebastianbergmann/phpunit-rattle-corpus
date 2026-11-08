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

use function sprintf;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Event\Test\PropertyFalsified;
use PHPUnit\Event\Test\PropertyFalsifiedSubscriber as PropertyFalsifiedSubscriberInterface;
use Throwable;

/**
 * Stores the choice sequences of the counterexamples of a falsified property
 * in the corpus of the property: the choice sequence of the minimal
 * counterexample and, unless this is turned off, the choice sequence of each
 * other way in which the property was falsified. A property that an explicit
 * example falsified has no choices, so nothing is stored for it.
 *
 * Storing a choice sequence must not change the outcome of the test run, so
 * the first choice sequence that cannot be stored is reported as a PHPUnit
 * warning, and the choice sequences that cannot be stored after it are not.
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/rattle-corpus
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/rattle-corpus
 */
final class PropertyFalsifiedSubscriber implements PropertyFalsifiedSubscriberInterface
{
    private readonly Corpus $corpus;
    private readonly bool $otherFalsifications;
    private bool $warned = false;

    /**
     * @param bool $otherFalsifications whether the choice sequences of the other ways in which a property was falsified are stored
     */
    public function __construct(Corpus $corpus, bool $otherFalsifications)
    {
        $this->corpus              = $corpus;
        $this->otherFalsifications = $otherFalsifications;
    }

    public function notify(PropertyFalsified $event): void
    {
        $sequences = [$event->counterexample()->choiceSequence()];

        if ($this->otherFalsifications) {
            foreach ($event->otherFalsifications() as $otherFalsification) {
                $sequences[] = $otherFalsification->counterexample()->choiceSequence();
            }
        }

        foreach ($sequences as $choices) {
            if ($choices === []) {
                continue;
            }

            try {
                $this->corpus->add($event->test()->id(), $choices);
            } catch (Throwable $t) {
                $this->warn($event, $t);

                return;
            }
        }
    }

    private function warn(PropertyFalsified $event, Throwable $t): void
    {
        if ($this->warned) {
            return;
        }

        $this->warned = true;

        EventFacade::emitter()->testRunnerTriggeredPhpunitWarning(
            sprintf(
                'The counterexamples of %s cannot be stored in the corpus of rattle: %s',
                $event->test()->id(),
                $t->getMessage(),
            ),
        );
    }
}
