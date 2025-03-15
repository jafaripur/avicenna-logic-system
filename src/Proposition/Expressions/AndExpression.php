<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Class AndExpression.
 *
 * Represents a conjunction (logical AND) between two  expressions.
 *
 * Constructor:
 *   - Accepts two LogicalExpression objects: $left and $right.
 *
 * evaluate():
 *   - Returns true only if both the left and right  expressions evaluate to true.
 */
class AndExpression extends LogicalExpression
{
    public function __construct(public LogicalExpression $left, public LogicalExpression $right)
    {
    }

    /**
     * Evaluates the conjunction.
     * Returns true if and only if both left and right evaluate to true.
     *
     * @param array $context an associative array mapping variable names to booleans
     *
     * @return bool true if both operands are true; otherwise, false
     */
    public function evaluate(array $context): bool
    {
        return $this->left->evaluate($context) && $this->right->evaluate($context);
    }
}
