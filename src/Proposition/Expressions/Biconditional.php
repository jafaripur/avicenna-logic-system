<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Class Biconditional.
 *
 * Represents a biconditional (logical equivalence) between two  expressions.
 *
 * Constructor:
 *   - Accepts two LogicalExpression objects: $left and $right.
 *
 * evaluate():
 *   - Returns true if both  expressions evaluate to the same truth value.
 */
class Biconditional extends LogicalExpression
{
    public function __construct(public LogicalExpression $left, public LogicalExpression $right)
    {
    }

    /**
     * Evaluates the equivalence.
     * Returns true if both left and right  expressions have the same truth value.
     *
     * @param array $context an associative array mapping variable names to booleans
     *
     * @return bool true if both sides are equal in truth value; otherwise, false
     */
    public function evaluate(array $context): bool
    {
        return $this->left->evaluate($context) === $this->right->evaluate($context);
    }
}
