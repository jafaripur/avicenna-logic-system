<?php

namespace Avicenna\Argument;

use Avicenna\Proposition\Proof;

/**
 * Class ArgumentsParserResult.
 *
 * This class encapsulates the result of parsing an argument (sequent).
 * It contains an array of Proof objects representing the premises,
 * and a single Proof object representing the conclusion.
 *
 * The constructor receives:
 *   - $premises: An array of strings, each representing a premise.
 *   - $conclusion: A string representing the conclusion.
 *
 * The premises are converted into Proof objects with an auto-detected line number,
 * and the conclusion is converted into a Proof object as well.
 */
final class ArgumentsParserResult
{
    /**
     * @var Proof[] array of Proof objects representing the premises
     */
    private array $premises = [];

    /**
     * @var Proof the Proof object representing the conclusion
     */
    private Proof $conclusion;

    /**
     * Constructor.
     *
     * @param array  $premises   an array of strings, each a premise of the argument
     * @param string $conclusion a string representing the conclusion of the argument
     */
    public function __construct(
        array $premises,
        string $conclusion,
    ) {
        // The conclusion is assigned a line number one more than the number of premises.
        $line = count($premises) + 1;

        $refrences = [];
        for ($i = 1; $i <= count($premises); ++$i) {
            $refrences[] = $i;
        }

        // Create a new Proof object for the conclusion.
        // Here, the conclusion's references array contains its own line number.
        $this->conclusion = new Proof($line, $conclusion, $refrences, null, null, null, []);

        // Iterate over each premise to create corresponding Proof objects.
        foreach ($premises as $key => $premise) {
            if (empty($premise)) {
                $this->premises[] = new Proof(1, '', [1], null, null, 'Assume', ['Assume']);
            } else {
                $line = $key + 1;
                $this->premises[] = new Proof($line, $premise, [$line], null, null, 'Premise', ['Premise']);
            }
        }
    }

    /**
     * Returns the array of Proof objects representing the premises.
     *
     * @return Proof[] array of Proof objects for premises
     */
    public function getPremises(): array
    {
        return $this->premises;
    }

    /**
     * Returns the Proof object representing the conclusion.
     *
     * @return Proof the conclusion Proof object
     */
    public function getConclusion(): Proof
    {
        return $this->conclusion;
    }
}
