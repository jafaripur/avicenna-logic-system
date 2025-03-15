<?php

namespace Avicenna\TruthTable;

use Avicenna\Proposition\Expressions\LogicalExpression;
use Avicenna\Proposition\Proof;
use Avicenna\Proposition\SubExpressionExtractor;

/**
 * Class TruthTableAnalyzer.
 *
 * This class analyzes a set of proofs by generating a truth table for all atomic variables
 * present in the proofs, collecting compound subexpressions, evaluating these expressions over
 * all truth assignments, classifying the results (as Tautology, Contradiction, or Contingent),
 * and finally determining the overall validity of the argument.
 */
final class TruthTableAnalyzer
{
    /**
     * Analyzes the given proofs by generating the truth table, evaluating each proof and subexpression,
     * classifying the evaluation results, and checking the overall validity of the argument.
     *
     * @param Proof[] $proofs an array of Proof objects
     *
     * @return TruthTableAnalyzerResult The complete analysis result, including:
     *                                  - variables: atomic variables.
     *                                  - combinations: truth table rows.
     *                                  - subExpressions: collected compound subexpressions.
     *                                  - expressionMap: mapping of subexpression objects to strings.
     *                                  - proofs: evaluated results and classification for each proof and subexpression.
     *                                  - valid: overall validity of the argument.
     *                                  - counterExamples: truth assignments that serve as counterexamples.
     */
    public static function analyze(array $proofs): TruthTableAnalyzerResult
    {
        $result = new TruthTableAnalyzerResult();
        $result->variables = static::extractVariables($proofs);
        $result->combinations = static::generateTruthTable($result->variables);

        // Extract subexpressions from all arguments
        $result->subExpressions = static::collectSubExpressions($proofs);

        // Create a mapping of expressions to strings
        $result->expressionMap = static::createExpressionMap($result->subExpressions);

        // Evaluate subexpressions
        static::evaluateSubExpressions($result);

        foreach ($proofs as $proof) {
            $proofResults = [];
            foreach ($result->combinations as $row) {
                $proofResults[] = $proof->ast->evaluate($row);
            }
            $result->proofs[$proof->line] = [
                'expression' => Proof::expressionToString($proof->ast),
                'results' => $proofResults,
                'classification' => static::classifyResults($proofResults),
            ];
        }

        static::analyzeValidity($proofs, $result);

        return $result;
    }

    /**
     * Collects all compound subexpressions (non-atomic) from all proofs.
     *
     * @param Proof[] $proofs an array of Proof objects
     *
     * @return array an array of compound LogicalExpression objects, sorted by their complexity
     */
    private static function collectSubExpressions(array $proofs): array
    {
        $allExpressions = [];
        // For each proof, extract subexpressions using the SubExpressionExtractor.
        foreach ($proofs as $proof) {
            $allExpressions = array_merge(
                $allExpressions,
                SubExpressionExtractor::extract($proof->ast)
            );
        }

        // Sort the collected subexpressions by their complexity.
        return static::sortByComplexity($allExpressions);
    }

    /**
     * Creates a mapping of each subexpression object (identified by its object hash)
     * to its standardized string representation.
     *
     * @param array $expressions an array of compound LogicalExpression objects
     *
     * @return array an associative array where keys are object hashes and values are string representations
     */
    private static function createExpressionMap(array $expressions): array
    {
        $map = [];
        foreach ($expressions as $expr) {
            $map[spl_object_hash($expr)] = Proof::expressionToString($expr);
        }

        return $map;
    }

    /**
     * Evaluates each subexpression for every truth assignment and stores the results in the analyzer result.
     *
     * For each combination in the truth table, the method evaluates every subexpression,
     * and stores the boolean result in the $result->proofs array under a key prefixed with 'sub_'.
     *
     * @param TruthTableAnalyzerResult $result the result object to update
     */
    private static function evaluateSubExpressions(TruthTableAnalyzerResult $result): void
    {
        foreach ($result->combinations as $rowIndex => $row) {
            foreach ($result->subExpressions as $expr) {
                $result->proofs['sub_'.spl_object_hash($expr)]['results'][$rowIndex] =
                    $expr->evaluate($row);
            }
        }
    }

    /**
     * Sorts an array of LogicalExpression objects by their complexity.
     *
     * Complexity is measured by the depth of the AST.
     *
     * @param array $expressions an array of LogicalExpression objects
     *
     * @return array the sorted array of LogicalExpression objects (least complex first)
     */
    private static function sortByComplexity(array $expressions): array
    {
        usort($expressions, function ($a, $b) {
            return static::getDepth($a) <=> static::getDepth($b);
        });

        return $expressions;
    }

    /**
     * Recursively calculates the depth of a LogicalExpression AST.
     *
     * Atomic variables have depth 0. For compound  expressions, the depth is 1 plus the maximum depth of its children.
     *
     * @param LogicalExpression $node the AST node
     *
     * @return int the depth of the node
     */
    private static function getDepth(LogicalExpression $node): int
    {
        if (LogicalExpression::isVariable($node)) {
            return 0;
        }
        if (LogicalExpression::isNegation($node)) {
            return 1 + static::getDepth($node->proposition);
        }

        /*
         * @var AndExpression|Or| Implication|Biconditional $node
         */
        return 1 + max(
            static::getDepth($node->left),
            static::getDepth($node->right)
        );
    }

    /**
     * An array of atomic variable names extracted from the proofs.
     *
     * @param Proof[] $proofs an array of Proof objects
     */
    private static function extractVariables(array $proofs): array
    {
        $variables = [];
        // Traverse the AST of each proof and collect atomic variables.
        foreach ($proofs as $proof) {
            static::traverseAST($proof->ast, $variables);
        }

        // Remove duplicates and return as a sequential array.
        return array_values(array_unique($variables));
    }

    /**
     * Recursively traverses the AST of a proposition and collects information.
     *
     * @param LogicalExpression $node               the current AST node
     * @param array             &$collector         A reference to the collector array (for variables or expressions)
     * @param bool              $collectExpressions if true, collects the entire node (for compound subexpressions);
     *                                              if false, collects only atomic variable names
     */
    private static function traverseAST(
        LogicalExpression $node,
        array &$collector,
        bool $collectExpressions = false
    ): void {
        if ($collectExpressions) {
            $collector[] = $node;
        }

        if (LogicalExpression::isNegation($node)) {
            static::traverseAST($node->proposition, $collector, $collectExpressions);
        } elseif (LogicalExpression::isAnd($node)
                 || LogicalExpression::isOr($node)
                 || LogicalExpression::isImplication($node)
                 || LogicalExpression::isBiconditional($node)) {
            static::traverseAST($node->left, $collector, $collectExpressions);
            static::traverseAST($node->right, $collector, $collectExpressions);
        } elseif (LogicalExpression::isVariable($node) && !$collectExpressions) {
            $collector[] = $node->getName();
        }
    }

    /**
     * Generates the truth table for the provided atomic variables.
     *
     * @param array $variables an array of atomic variable names
     *
     * @return array an array of truth assignments, where each assignment is an associative array
     *               mapping variable names to boolean values
     */
    private static function generateTruthTable(array $variables): array
    {
        $numVariables = count($variables);
        $totalCombinations = pow(2, $numVariables);

        $combinations = [];

        // Loop through each possible combination of truth values.
        for ($i = 0; $i < $totalCombinations; ++$i) {
            $combination = [];
            foreach ($variables as $index => $var) {
                // Calculate truth value using bitwise operation.
                $combination[$var] = (bool) ($i & (1 << ($numVariables - 1 - $index)));
            }
            $combinations[] = $combination;
        }

        return $combinations;
    }

    /**
     * Classifies the evaluation results of a proof or subexpression over all truth table rows.
     *
     * @param array $results an array of boolean values
     *
     * @return string returns 'Tautology' if all values are true, 'Contradiction' if all false, or 'Contingent' otherwise
     */
    private static function classifyResults(array $results): string
    {
        $allTrue = !in_array(false, $results, true);
        $allFalse = !in_array(true, $results, true);

        return match (true) {
            $allTrue => 'Tautology',
            $allFalse => 'Contradiction',
            default => 'Contingent'
        };
    }

    /**
     * Analyzes the overall validity of the argument.
     * For every truth assignment where all premises are true, the conclusion must also be true.
     * Any counterexample (where premises are true but the conclusion is false) is stored.
     *
     * @param Proof[]                  $proofs an array of Proof objects
     * @param TruthTableAnalyzerResult $result the analysis result to update
     */
    private static function analyzeValidity(array $proofs, TruthTableAnalyzerResult $result): void
    {
        $premises = array_filter($proofs, fn ($p) => 'Premise' === $p->autoType);
        $conclusion = end($proofs);

        foreach ($result->combinations as $i => $row) {
            $allPremisesTrue = true;
            foreach ($premises as $premise) {
                if (!$result->proofs[$premise->line]['results'][$i]) {
                    $allPremisesTrue = false;

                    break;
                }
            }

            if ($allPremisesTrue && !$result->proofs[$conclusion->line]['results'][$i]) {
                $result->valid = false;
                $result->counterExamples[] = $row;
            }
        }
    }
}
