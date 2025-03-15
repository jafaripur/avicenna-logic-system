<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Class XorExpression.
 *
 * Represents an exclusive disjunction (logical XOR) between two expressions.
 *
 * Constructor:
 *   - Accepts two LogicalExpression objects: $left and $right.
 *
 * evaluate():
 *   - Returns true if exactly one of the expressions is true, but not both.
 */
class XorExpression extends LogicalExpression
{
    public function __construct(public LogicalExpression $left, public LogicalExpression $right)
    {
    }

    /**
     * Evaluates the exclusive disjunction (XOR).
     * Returns true if exactly one of the operands is true, but not both.
     *
     * @param array $context an associative array mapping variable names to booleans
     *
     * @return bool true if exactly one operand is true; otherwise, false
     */
    public function evaluate(array $context): bool
    {
        return $this->left->evaluate($context) !== $this->right->evaluate($context);
    }
}
