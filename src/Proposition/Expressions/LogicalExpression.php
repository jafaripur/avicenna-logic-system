<?php

namespace Avicenna\Proposition\Expressions;

/**
 * Abstract class LogicalExpression.
 *
 * Represents a logical proposition that can be evaluated given a context.
 * Every logical expression in our system extends this class and must implement the evaluate() method.
 *
 * Input: An associative array (context) mapping variable names to boolean values.
 * Output: A boolean value representing the truth value of the proposition under the given context.
 */
abstract class LogicalExpression
{
    // Proposition Logic
    public const OPERATOR_IMPLICATION = '→';
    public const OPERATOR_CONJUNCTION = '∧';
    public const OPERATOR_DISJUNCTION = '∨';
    public const OPERATOR_NEGATION = '¬';
    public const OPERATOR_BICONDITIONAL = '↔';

    /**
     * Evaluates the proposition against the provided context.
     *
     * @param array $context an associative array where keys are variable names and values are booleans
     *
     * @return bool the truth value of the proposition
     */
    abstract public function evaluate(array $context): bool;

    /**
     * Checks if the given proposition is an implication.
     *
     * @param mixed $prop the value to check
     *
     * @return bool true if the proposition is an instance of  Implication, otherwise false
     */
    public static function isImplication(mixed $prop): bool
    {
        return $prop instanceof Implication;
    }

    /**
     * Checks if the given proposition is a negation.
     *
     * @param mixed $prop the value to check
     *
     * @return bool true if the proposition is an instance of Negation, otherwise false
     */
    public static function isNegation(mixed $prop): bool
    {
        return $prop instanceof Negation;
    }

    /**
     * Checks if the given proposition is an "And" (conjunction) proposition.
     *
     * @param mixed $prop the value to check
     *
     * @return bool returns true if $prop is an instance of AndExpression, otherwise false
     */
    public static function isAnd(mixed $prop): bool
    {
        return $prop instanceof AndExpression;
    }

    /**
     * Checks if the given proposition is an "Or" (disjunction) proposition.
     *
     * @param mixed $prop the value to check
     *
     * @return bool returns true if $prop is an instance of OrExpression, otherwise false
     */
    public static function isOr(mixed $prop): bool
    {
        return $prop instanceof OrExpression;
    }

    /**
     * Checks if the given proposition represents a logical equivalence.
     *
     * @param mixed $prop the value to check
     *
     * @return bool returns true if $prop is an instance of Biconditional, otherwise false
     */
    public static function isBiconditional(mixed $prop): bool
    {
        return $prop instanceof Biconditional;
    }

    /**
     * Checks if the given value is a Variable.
     *
     * This method accepts either a string or a LogicalExpression. It returns true
     * if the provided value is an instance of Variable.
     *
     * @param mixed $prop the value to check
     *
     * @return bool returns true if $prop is a Variable, otherwise false
     */
    public static function isVariable(mixed $prop): bool
    {
        return $prop instanceof Variable;
    }
}
