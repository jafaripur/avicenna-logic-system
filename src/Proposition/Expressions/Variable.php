<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Class Variable.
 *
 * Represents an atomic proposition (a variable) in logic.
 *
 * Constructor:
 *   - Accepts a string $name representing the variable name.
 *
 * evaluate():
 *   - Returns the boolean value from the context corresponding to the variable's name.
 *
 * getName():
 *   - Returns the name of the variable.
 */
final class Variable extends LogicalExpression
{
    public function __construct(private string $name)
    {
    }

    /**
     * Evaluates the variable by looking up its value in the context.
     *
     * @param array $context an associative array mapping variable names to booleans
     *
     * @return bool the value of the variable from the context, or false if not set
     */
    public function evaluate(array $context): bool
    {
        return $context[$this->name] ?? false;
    }

    /**
     * Returns the name of the variable.
     *
     * @return string the variable name
     */
    public function getName(): string
    {
        return $this->name;
    }
}
