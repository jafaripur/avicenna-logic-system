<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Class Negation.
 *
 * Represents a negation (logical NOT) of a proposition.
 *
 * Constructor:
 *   - Accepts a LogicalExpression object $proposition that is to be negated.
 *
 * evaluate():
 *   - Returns the negation of the evaluation of the inner proposition.
 */
class Negation extends LogicalExpression
{
    public function __construct(public LogicalExpression $proposition)
    {
    }

    /**
     * Evaluates the negation: returns the opposite of the inner proposition's value.
     *
     * @param array $context an associative array mapping variable names to booleans
     *
     * @return bool the negated truth value of the proposition
     */
    public function evaluate(array $context): bool
    {
        return !$this->proposition->evaluate($context);
    }
}
