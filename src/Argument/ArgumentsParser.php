<?php

namespace Avicenna\Argument;

use Avicenna\Exception\ArgumentConclusionException;

/**
 * Class ArgumentsParser.
 *
 * This class provides a static method to parse a logical argument
 * in the form of a string into an ArgumentsParserResult.
 * The expected format of the argument string is:
 *     <premises> ⊢ <conclusion>
 * where the left-hand side contains the premises (possibly separated by commas)
 * and the right-hand side contains the conclusion.
 */
final class ArgumentsParser
{
    /**
     * Parses the given argument string into premises and a conclusion.
     *
     * @param string $argument The input argument string in the format "<premises> ⊢ <conclusion>".
     *                         Example: "P, Q ⊢ R"
     *
     * @return ArgumentsParserResult an object containing an array of premises and the conclusion
     *
     * @throws ArgumentConclusionException if the argument does not contain exactly one '⊢' symbol
     */
    public static function create(string $argument): ArgumentsParserResult
    {
        // Trim the input string to remove any leading or trailing whitespace.
        $argument = trim($argument);

        // Split the argument by the turnstile symbol (⊢) to separate premises and conclusion.
        // Using explode ensures that only one occurrence of '⊢' splits the string.

        $argument = str_replace('∴', '⊢', $argument);
        $parts = explode('⊢', $argument);

        // Check if the argument splits into exactly two parts.
        if (2 !== count($parts)) {
            throw new ArgumentConclusionException("Invalid argument format. Expected a single '⊢' symbol.");
        }

        // Extract the left-hand side (premises) and right-hand side (conclusion) after trimming.
        $premisesString = trim($parts[0]);
        $conclusion = trim($parts[1]);

        // If the premises string contains a comma, split it into multiple formulas.
        // Otherwise, treat the entire string as a single premise.
        if (false !== str_contains($premisesString, ',')) {
            $premises = array_map('trim', explode(',', $premisesString));
        } else {
            $premises = [$premisesString];
        }

        // Return a new ArgumentsParserResult containing the premises and conclusion.
        return new ArgumentsParserResult($premises, $conclusion);
    }
}
