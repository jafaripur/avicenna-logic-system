<?php

namespace Avicenna\Proposition;

use Avicenna\Proposition\Expressions\LogicalExpression;

/**
 * Class LogicalExpressionComparator.
 *
 * This class provides a static method to compare two LogicalExpression objects for structural equality.
 * It supports comparing variables, negations, conjunctions, disjunctions, implications, and equivalences.
 * For commutative operators (And, OrExpression, Biconditional), it checks equality in both possible orders.
 */
final class LogicalExpressionComparator
{
    /**
     * Determines whether two LogicalExpression objects are equal.
     *
     * @param LogicalExpression $a the first proposition
     * @param LogicalExpression $b the second proposition
     *
     * @return bool returns true if the  expressions are structurally equal; otherwise, false
     */
    public static function areEqual(LogicalExpression $a, LogicalExpression $b): bool
    {
        // If both  expressions are exactly the same instance, they are equal.
        if ($a === $b) {
            return true;
        }

        // If they are of different classes, they cannot be equal.
        if (get_class($a) !== get_class($b)) {
            return false;
        }

        // If the  expressions are Variables, compare their names.
        if (LogicalExpression::isVariable($a)) {
            /*
             * @var Variable $b
             */
            return $a->getName() === $b->getName();
        }

        // If the  expressions are Negations, compare the inner  expressions recursively.
        if (LogicalExpression::isNegation($a)) {
            /*
             * @var Negation $b
             */
            return self::areEqual($a->proposition, $b->proposition);
        }

        // For commutative operators such as AndExpression and OrExpression, compare both possible orders.
        if (LogicalExpression::isAnd($a) || LogicalExpression::isOr($a)) {
            /*
             * @var AndExpression|Or $a
             * @var AndExpression|Or $b
             */
            return (
                self::areEqual($a->left, $b->left)
                && self::areEqual($a->right, $b->right)
            ) || (
                self::areEqual($a->left, $b->right)
                && self::areEqual($a->right, $b->left)
            );
        }

        // For equivalence, which is also commutative, compare both orders.
        if (LogicalExpression::isBiconditional($a)) {
            /*
             * @var Biconditional $a
             * @var Biconditional $b
             */
            return (
                self::areEqual($a->left, $b->left)
                && self::areEqual($a->right, $b->right)
            ) || (
                self::areEqual($a->left, $b->right)
                && self::areEqual($a->right, $b->left)
            );
        }

        // For implication, which is not commutative, compare left and right in order.
        if (LogicalExpression::isImplication($a)) {
            /*
             * @var  Implication $a
             * @var  Implication $b
             */
            return
                self::areEqual($a->left, $b->left)
                && self::areEqual($a->right, $b->right);
        }

        // If none of the above cases match, return false.
        return false;
    }
}
