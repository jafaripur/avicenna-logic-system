<?php

namespace Avicenna\Method;

use Avicenna\Argument\ArgumentsParserResult;

/**
 * Abstract class SyntaxMethod.
 *
 * This abstract class defines the interface for parsing and rendering logical expressions.
 * It declares methods for:
 * - Parsing an input string into tokens/AST,
 * - Rendering proofs (e.g., into SVG or other formats),
 * - Outputting formatted proofs,
 * - Outputting an argument (using an ArgumentsParserResult).
 */
abstract class SyntaxMethod
{
    /**
     * Parses the input string and returns an array of proof objects.
     *
     * @param string $input     the input string containing the logical expression or argument
     * @param int    $logicType The type of logic to use (e.g., LogicParser::PROPOSITIONAL).
     *
     * @return array an array of Proof objects
     */
    abstract public static function parseInput(string $input, int $logicType): array;

    /**
     * Renders an array of Proof objects into a formatted output (e.g., SVG).
     *
     * @param array $proofs an array of Proof objects
     *
     * @return string the rendered output as a string
     */
    abstract public static function render(array $proofs): string;

    /**
     * Outputs the formatted proofs to the console or another output medium.
     *
     * @param array $proofs an array of Proof objects
     */
    abstract public static function getOutput(array $proofs): void;

    /**
     * Outputs the argument (i.e., the parsed premises and conclusion) in a formatted way.
     *
     * @param ArgumentsParserResult $proofs the result object from argument parsing
     */
    abstract public static function getOutputArgument(ArgumentsParserResult $proofs): void;
}
