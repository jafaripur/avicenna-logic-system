<?php

namespace Avicenna\Proposition;

use Avicenna\Proposition\Expressions\LogicalExpression;

/**
 * Class SubExpressionExtractor.
 *
 * This class provides functionality to extract all compound subexpressions (non-atomic) from a LogicalExpression AST.
 * It traverses the AST recursively and collects all nodes representing compound expressions,
 * such as Negation, AndExpression, OrExpression,  Implication, and Biconditional.
 */
final class SubExpressionExtractor
{
    /**
     * Extracts all unique compound subexpressions from a given proposition.
     *
     * @param LogicalExpression $node the root of the proposition AST
     *
     * @return array an array of unique LogicalExpression objects representing the compound subexpressions
     */
    public static function extract(LogicalExpression $node): array
    {
        $expressions = [];
        self::traverse($node, $expressions);

        // array_unique with SORT_REGULAR is used to remove duplicate subexpressions.
        return array_values(array_unique($expressions, SORT_REGULAR));
    }

    /**
     * Recursively traverses the AST of a proposition to collect compound subexpressions.
     *
     * @param LogicalExpression $node         the current AST node
     * @param array             &$expressions A reference to an array that collects compound subexpressions.
     *
     * This method adds compound expressions (Negation, AndExpression, OrExpression,  Implication, Biconditional)
     * to the $expressions array. Atomic variables are not collected.
     */
    private static function traverse(LogicalExpression $node, array &$expressions): void
    {
        // For a negation, traverse its inner proposition and then add the negation node.
        if (LogicalExpression::isNegation($node)) {
            self::traverse($node->proposition, $expressions);
            $expressions[] = $node;
        }
        // For compound operators (And, OrExpression,  Implication, Biconditional), traverse both children and add the node.
        elseif (LogicalExpression::isAnd($node)
               || LogicalExpression::isOr($node)
               || LogicalExpression::isImplication($node)
               || LogicalExpression::isBiconditional($node)) {
            self::traverse($node->left, $expressions);
            self::traverse($node->right, $expressions);
            $expressions[] = $node;
        }

        // Atomic variables are not added when extracting compound expressions.
    }
}
