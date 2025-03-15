<?php

namespace Avicenna\TruthTable;

/**
 * Class TruthTableAnalyzerResult.
 *
 * This class holds the results of the truth table analysis for a given argument.
 *
 * Properties:
 * - $variables: An array of atomic variable names used in the argument.
 * - $combinations: An array of truth assignments (rows of the truth table) for the variables.
 * - $proofs: An associative array where each key corresponds to a proof line (or a sub-expression key)
 *            and its value is an array containing:
 *              - 'results': an array of boolean evaluation results for each truth assignment.
 *              - 'expression': a string representation of the expression.
 *              - 'classification': the classification of the expression (e.g., Tautology, Contradiction, Contingent).
 * - $valid: A boolean indicating whether the overall argument is valid (i.e., whenever all premises are true, the conclusion is also true).
 * - $counterExamples: An array of truth assignments that serve as counterexamples to the argument’s validity.
 * - $subExpressions: An array of compound sub-expressions extracted from the proofs.
 * - $expressionMap: A mapping from a unique key (based on an object hash) of a sub-expression to its string representation.
 */
final class TruthTableAnalyzerResult
{
    public array $variables = [];
    public array $combinations = [];
    public array $proofs = [];
    public bool $valid = true;
    public array $counterExamples = [];
    public array $subExpressions = [];
    public array $expressionMap = [];
}
