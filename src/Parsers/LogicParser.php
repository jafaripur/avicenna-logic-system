<?php

namespace Avicenna\Parsers;

use Avicenna\Exception\UnsupportedLogicTypeException;
use Avicenna\Proposition\Expressions\LogicalExpression;

abstract class LogicParser
{
    public const PROPOSITIONAL = 1;

    /**
     * Parses an array of tokens into a LogicalExpression (AST).
     *
     * @param array $tokens an array of tokens from the Tokenizer
     *
     * @return LogicalExpression The root node of the AST representing the parsed formula.
     *
     * The method implements the Shunting-yard algorithm to convert the tokens into
     * postfix notation and then builds an AST from it.
     */
    abstract public function parse(array $tokens): LogicalExpression;

    /**
     * Parses a logical formula into a LogicalExpression AST.
     *
     * @param string $formula   the input logical formula
     * @param int    $logicType The type of logic to use (e.g., LogicParser::PROPOSITIONAL).
     *
     * @return LogicalExpression the parsed abstract syntax tree of the formula
     */
    public static function parseFormula(string $formula, int $logicType = LogicParser::PROPOSITIONAL): LogicalExpression
    {
        $parser = static::create($logicType);

        return $parser->parse($parser->tokenize($formula));
    }

    /**
     * Tokenizes the input string into an array of tokens.
     *
     * Example:
     *   Input: "(p ∧ ¬(q → (r ↔ s))) → t"
     *   Output: ['(', 'p', '∧', '¬', '(', 'q', '→', '(', 'r', '↔', 's', ')', ')', ')', '→', 't']
     *
     * Example:
     *   Input: "(p ∧ ¬q) → (r ∨ s)"
     *   Output: ['(', 'p', '∧', '¬', 'q', ')', '→', '(', 'r', '∨', 's', ')']
     *
     * @param string $input the input string containing a logical formula
     *
     * @return array an array of tokens (strings) with operators normalized to standard symbols
     */
    abstract protected function tokenize(string $input): array;

    /**
     * Creates and returns an appropriate LogicParser instance based on the logic type.
     *
     * @param int $logicType The type of logic (e.g., LogicParser::PROPOSITIONAL).
     *
     * @return LogicParser a parser instance corresponding to the specified logic type
     *
     * @throws UnsupportedLogicTypeException if the provided logic type is not supported
     */
    private static function create(int $logicType): LogicParser
    {
        return match ($logicType) {
            LogicParser::PROPOSITIONAL => new PropositionalParser(),
            // LogicParser::PROPOSITIONAL_NEW => new PropositionalParserNew(),
            default => throw new UnsupportedLogicTypeException('Unsupported logic type')
        };
    }
}
