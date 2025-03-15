<?php

namespace Avicenna\Parsers;

use Avicenna\Exception\InsufficientOperandException;
use Avicenna\Exception\MalformedExpressionException;
use Avicenna\Exception\MissingNotOperandException;
use Avicenna\Exception\UnbalancedParenthesesException;
use Avicenna\Exception\UnknownOperandException;
use Avicenna\Proposition\Expressions\AndExpression;
use Avicenna\Proposition\Expressions\Biconditional;
use Avicenna\Proposition\Expressions\Implication;
use Avicenna\Proposition\Expressions\LogicalExpression;
use Avicenna\Proposition\Expressions\Negation;
use Avicenna\Proposition\Expressions\OrExpression;
use Avicenna\Proposition\Expressions\Variable;
use Avicenna\Proposition\Expressions\XorExpression;

/**
 * Final class PropositionalParser.
 *
 * This class implements the LogicParser interface for propositional logic.
 * It uses the Shunting-yard algorithm to parse an array of tokens into an Abstract Syntax Tree (AST)
 * representing the propositional formula.
 */
final class PropositionalParser extends LogicParser
{
    /**
     * Regular expression used for matching tokens.
     * This regex matches:
     * - AND operators (e.g., ∧, &&, &, /\\, AND)
     * - OR operators (e.g., ∨, ||, /\\, OR)
     * - IMPLIES operators (e.g., →, ->, IMP)
     * - NOT operators (e.g., ¬, ~, !, NOT)
     * - EQUIVALENCE operators (e.g., ↔, ≡, <->, EQ)
     * - XOR operators (e.g., ⊕, ⊻, XOR)
     * - Variables (letters, numbers, underscores)
     * - Parentheses.
     */
    private const TOKEN_REGEX = '/
        (?:∧|&&|&|\/\\\\|AND) |          # AND
        (?:∨|\|\||\\\\\/|OR)  |          # OR
        (?:→|->|IMP)          |          # IMPLIES
        (?:¬|~|!|NOT)         |          # NOT
        (?:↔|≡|<->|EQ)        |          # EQUIVALENCE|Biconditional
        (?:⊕|⊻|XOR)           |          # XOR|Exclusive Disjuction
        ([\p{L}\p{N}_]+)      |          # Variables (including letters, numbers, and underscores)
        ([()])                           # Parentheses
    /ux';

    /**
     * Precedence values for operators.
     * Higher numbers indicate higher precedence.
     */
    private const PRECEDENCE = [
        LogicalExpression::OPERATOR_NEGATION => 5,
        LogicalExpression::OPERATOR_CONJUNCTION => 4,
        LogicalExpression::OPERATOR_DISJUNCTION => 3,
        LogicalExpression::OPERATOR_XOR => 3,
        LogicalExpression::OPERATOR_IMPLICATION => 2,
        LogicalExpression::OPERATOR_BICONDITIONAL => 1,
    ];

    /**
     * Associativity for operators.
     * 'left' or 'right' indicates the associativity of the operator.
     */
    private const ASSOCIATIVITY = [
        LogicalExpression::OPERATOR_NEGATION => 'right',
        LogicalExpression::OPERATOR_CONJUNCTION => 'left',
        LogicalExpression::OPERATOR_DISJUNCTION => 'left',
        LogicalExpression::OPERATOR_XOR => 'left',
        LogicalExpression::OPERATOR_IMPLICATION => 'right',
        LogicalExpression::OPERATOR_BICONDITIONAL => 'left',
    ];

    /**
     * {@inheritDoc}
     */
    public function parse(array $tokens): LogicalExpression
    {
        $output = [];
        $operatorStack = [];

        // Process each token
        foreach ($tokens as $token) {
            if ('(' === $token) {
                // Push opening parenthesis onto the operator stack
                array_push($operatorStack, $token);
            } elseif (')' === $token) {
                // Process until an opening parenthesis is found
                $this->handleClosingParenthesis($output, $operatorStack);
            } elseif ($this->isOperator($token)) {
                // Handle operator tokens using precedence and associativity
                $this->handleOperator($token, $output, $operatorStack);
            } else {
                // For variables, create a new Variable node and add to output
                $output[] = new Variable($token);
            }
        }

        // Pop any remaining operators from the stack into the output.
        while (!empty($operatorStack)) {
            $output[] = array_pop($operatorStack);
        }

        // Build the AST from the postfix notation
        return $this->buildAST($output);
    }

    /**
     * {@inheritDoc}
     */
    protected function tokenize(string $input): array
    {
        preg_match_all(self::TOKEN_REGEX, $input, $matches, PREG_SET_ORDER);

        return array_map(function ($match) {
            $token = $match[0];

            // Normalize operators to standard symbols using a match expression.
            return match (strtoupper($token)) {
                'AND', '/\\', '&&', '&' => '∧',
                'OR', '\\/', '||', '|' => '∨',
                'IMP', '->' => '→',
                'NOT', '~', '!' => '¬',
                'EQ', '<->', '≡' => '↔',
                default => $token, // Return the original token if no match
            };
        }, $matches);
    }

    /**
     * Handles a closing parenthesis.
     *
     * Pops operators from the operator stack until an opening parenthesis is found.
     * Adds each popped operator to the output.
     *
     * @param array &$output        The output queue (postfix notation)
     * @param array &$operatorStack The operator stack
     *
     * @throws UnbalancedParenthesesException if no matching opening parenthesis is found
     */
    private function handleClosingParenthesis(array &$output, array &$operatorStack): void
    {
        $foundOpening = false;
        while (!empty($operatorStack)) {
            $op = array_pop($operatorStack);
            if ('(' === $op) {
                $foundOpening = true;

                break;
            }
            $output[] = $op;
        }
        if (!$foundOpening) {
            throw new UnbalancedParenthesesException('Unbalanced parentheses!');
        }
    }

    /**
     * Handles an operator token.
     *
     * While there are operators on the stack with higher precedence (or equal precedence if left-associative),
     * pop them off to the output queue. Then, push the current operator onto the stack.
     *
     * @param string $token          the operator token
     * @param array  &$output        The output queue
     * @param array  &$operatorStack The operator stack
     */
    private function handleOperator(string $token, array &$output, array &$operatorStack): void
    {
        while (!empty($operatorStack)) {
            $top = end($operatorStack);
            if ('(' === $top) {
                break;
            }

            $opPrec = self::PRECEDENCE[$top];
            $tokenPrec = self::PRECEDENCE[$token];
            $opAssoc = self::ASSOCIATIVITY[$top] ?? 'left';

            if (
                ('left' === $opAssoc && $opPrec >= $tokenPrec)
                || ('right' === $opAssoc && $opPrec > $tokenPrec)
            ) {
                $output[] = array_pop($operatorStack);
            } else {
                break;
            }
        }
        array_push($operatorStack, $token);
    }

    /**
     * Checks if a token is an operator.
     *
     * @param string $token the token to check
     *
     * @return bool true if the token is an operator; otherwise, false
     */
    private function isOperator(string $token): bool
    {
        return isset(self::PRECEDENCE[$token]);
    }

    /**
     * Builds an Abstract Syntax Tree (AST) from the postfix notation array.
     *
     * @param array $postfix the array in postfix notation containing Variable nodes and operator tokens
     *
     * @return LogicalExpression the root node of the constructed AST
     *
     * @throws InsufficientOperandException if there are not enough operands for an operator
     * @throws UnknownOperandException      if the unknow operand fonud
     * @throws MalformedExpressionException if the final AST is not singular
     */
    private function buildAST(array $postfix): LogicalExpression
    {
        $stack = [];
        foreach ($postfix as $token) {
            if (LogicalExpression::isVariable($token)) {
                array_push($stack, $token);
            } else {
                if (LogicalExpression::OPERATOR_NEGATION === $token) {
                    if (empty($stack)) {
                        throw new MissingNotOperandException('Missing operand for ¬');
                    }
                    $operand = array_pop($stack);
                    array_push($stack, new Negation($operand));
                } else {
                    if (count($stack) < 2) {
                        throw new InsufficientOperandException("Insufficient operands for {$token}");
                    }
                    $right = array_pop($stack);
                    $left = array_pop($stack);

                    switch ($token) {
                        case LogicalExpression::OPERATOR_CONJUNCTION: array_push($stack, new AndExpression($left, $right));

                            break;

                        case LogicalExpression::OPERATOR_DISJUNCTION: array_push($stack, new OrExpression($left, $right));

                            break;

                        case LogicalExpression::OPERATOR_XOR: array_push($stack, new XorExpression($left, $right));

                            break;

                        case LogicalExpression::OPERATOR_IMPLICATION: array_push($stack, new Implication($left, $right));

                            break;

                        case LogicalExpression::OPERATOR_BICONDITIONAL: array_push($stack, new Biconditional($left, $right));

                            break;

                        default: throw new UnknownOperandException("Unknown operator: {$token}");
                    }
                }
            }
        }
        if (1 !== count($stack)) {
            throw new MalformedExpressionException('Malformed expression');
        }

        return array_pop($stack);
    }
}
