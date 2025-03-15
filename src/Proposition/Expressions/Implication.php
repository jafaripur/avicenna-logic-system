<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Class  Implication.
 *
 * Represents a conditional (logical implication) between two  expressions.
 *
 * Constructor:
 *   - Accepts two LogicalExpression objects: $left (antecedent) and $right (consequent).
 *
 * evaluate():
 *   - Returns true if the antecedent is false or the consequent is true.
 */
class Implication extends LogicalExpression
{
    public function __construct(public LogicalExpression $left, public LogicalExpression $right)
    {
    }

    /**
     * Evaluates the implication.
     * According to the truth table for implication, the result is true if the antecedent is false
     * or the consequent is true.
     *
     * @param array $context an associative array mapping variable names to booleans
     *
     * @return bool the truth value of the implication
     */
    public function evaluate(array $context): bool
    {
        return !$this->left->evaluate($context) || $this->right->evaluate($context);
    }
}
