[![CI Status](https://github.com/sebastianbergmann/phpunit-rattle-corpus/workflows/CI/badge.svg)](https://github.com/sebastianbergmann/phpunit-rattle-corpus/actions)
[![codecov](https://codecov.io/gh/sebastianbergmann/phpunit-rattle-corpus/branch/main/graph/badge.svg)](https://codecov.io/gh/sebastianbergmann/phpunit-rattle-corpus)

# phpunit/rattle-corpus

PHPUnit test runner extension that copies the counterexamples of falsified properties into the corpus of [rattle](https://github.com/sebastianbergmann/rattle).

PHPUnit and rattle share their inputs: both represent examples as choice sequences of [`sebastian/choice-sequence`](https://github.com/sebastianbergmann/choice-sequence). When PHPUnit falsifies a property, it shrinks the counterexample and reports the minimal counterexample, together with the simplest counterexample for every other way in which the property was falsified while it was shrunk. These counterexamples reach code paths that make the property fail, often behind conditions that random generation rarely satisfies.

With this extension, every ordinary run of PHPUnit, locally or in CI, stores the choice sequences of these counterexamples in the corpus of the property in rattle's cache directory. `rattle fuzz --phpunit` replays the inputs in the corpus of a property first, so a counterexample that still fails becomes a finding. A counterexample that no longer fails stays in the corpus only when it produces a feature, an edge of the code or how often it is taken, that no smaller input in the corpus produces. Otherwise, rattle removes it from the corpus when fuzzing starts. Nobody has to move files by hand.

## Installation

You can add this extension as a development-time dependency to your project using [Composer](https://getcomposer.org/):

```
composer require --dev phpunit/rattle-corpus
```

The extension requires a version of PHPUnit that supports property-based testing, which is currently developed on the branch `feature/property-based-testing` of PHPUnit. It is only installed with Composer: PHPUnit 14 does not load test runner extensions from PHARs.

The extension does not depend on rattle. You can use rattle from its PHAR or install it with Composer.

## Usage

The extension is registered in the configuration file of PHPUnit:

```xml
<extensions>
    <bootstrap class="PHPUnit\RattleCorpus\Extension"/>
</extensions>
```

It has these parameters:

| Parameter | Default | Meaning |
|---|---|---|
| `cacheDirectory` | `.rattle.cache` | The cache directory of rattle, as given with `--cache-directory` to `rattle fuzz` |
| `otherFalsifications` | `true` | `false` to store only the choice sequence of the minimal counterexample of a property, not those of the other ways in which it was falsified |

```xml
<extensions>
    <bootstrap class="PHPUnit\RattleCorpus\Extension">
        <parameter name="cacheDirectory" value="build/rattle"/>
        <parameter name="otherFalsifications" value="false"/>
    </bootstrap>
</extensions>
```

A relative cache directory is relative to the directory of the configuration file of PHPUnit, or to the working directory when no configuration file is used, for instance when the extension is loaded with `--extension 'PHPUnit\RattleCorpus\Extension'`. Use the same cache directory for `rattle fuzz --phpunit`, relative to the directory in which you run rattle.

In CI, the corpus is only useful when the cache directory is kept between runs, for instance with the cache of your CI system.

## What is stored

When a property is falsified, the extension stores the choice sequence of its minimal counterexample, and the choice sequence of each other way in which it was falsified unless `otherFalsifications` is `false`. A property that an explicit example, given with `#[TestWith]` or `#[TestWithJson]`, falsified has no choices, so nothing is stored for it. Nothing is stored when no property is falsified.

The choice sequences are stored as rattle stores the inputs in the corpus of a property:

* The corpus of a property is the directory `<cache directory>/<property directory>/corpus`
* The name of the property directory is derived from the test ID of the property: the part of the test ID after its last `\`, with every run of characters other than `A-Z`, `a-z`, and `0-9` replaced with `-`, in lowercase and without `-` at its start and end, followed by `-` and the first 8 hexadecimal digits of the SHA-256 of the whole test ID. When nothing is left of the part of the test ID, the name is these 8 digits alone. For instance, the property `Vendor\Package\ParserTest::testRoundTrip` has the directory `parsertest-testroundtrip-dbe593bd`
* Each file holds one choice sequence in the format `choice-sequence v1`, and its name is the hexadecimal SHA-256 of its content, so a choice sequence is stored only once

Only choices are stored, no spans and no maxima: rattle replays every input in the corpus when fuzzing starts and records them then.

A file is written to a temporary file in the same directory first, which is then renamed. PHPUnit processes that run at the same time, with ParaTest, for instance, and that store the same choice sequence, therefore never see a file that is only partly written. The name of the temporary file starts with a dot, so rattle ignores it.

## When something goes wrong

The extension does not raise exceptions. When a choice sequence cannot be stored, because a directory cannot be created or a file cannot be written, this is reported once as a PHPUnit warning, and the choice sequences that cannot be stored after it are not reported. A choice sequence is only stored after a property was falsified, which already fails the test run, so the warning does not change the exit code of PHPUnit.

An invalid parameter, an empty `cacheDirectory` or an `otherFalsifications` other than `true` and `false`, is reported as a PHPUnit warning, and nothing is stored. By default, PHPUnit fails a test run in which a PHPUnit warning was triggered.

## Backward compatibility

Only the class `PHPUnit\RattleCorpus\Extension`, its parameters, and what it stores where are covered by the backward compatibility promise for this package. All other classes are internal.
