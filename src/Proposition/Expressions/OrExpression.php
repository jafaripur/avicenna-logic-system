<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Class OrExpression.
 *
 * Represents a disjunction (logical OR) between two  expressions.
 *
 * Constructor:
 *   - Accepts two LogicalExpression objects: $left and $right.
 *
 * evaluate():
 *   - Returns true if either the left or right proposition evaluates to true.
 */
class OrExpression extends LogicalExpression
{
    public function __construct(public LogicalExpression $left, public LogicalExpression $right)
    {
    }

    /**
     * Evaluates the disjunction: returns true if at least one of the operands is true.
     *
     * @param array $context an associative array mapping variable names to booleans
     *
     * @return bool true if either left or right evaluates to true; otherwise, false
     */
    public function evaluate(array $context): bool
    {
        return $this->left->evaluate($context) || $this->right->evaluate($context);
    }
}
