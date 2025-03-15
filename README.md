# Avicenna Logic System

![Dev](https://github.com/jafaripur/avicenna-logic-system/actions/workflows/dev.yml/badge.svg)
![Master](https://github.com/jafaripur/avicenna-logic-system/actions/workflows/master.yml/badge.svg)

![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-brightgreen)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A powerful tool for analyzing logical proofs and generating truth tables with propositional logic support.

A propositional logic parser and evaluator system.

This project is a comprehensive system for parsing and evaluating propositional logic expressions. It provides tools to tokenize, parse, and evaluate logical formulas, supporting standard logical operators such as AND (`∧`), OR (`∨`), NOT (`¬`), IMPLICATION (`→`), and BICONDITIONAL (`↔`). The system is designed to handle complex nested expressions and can evaluate them against a given context (a mapping of variables to boolean values).

## Key Features:
- `Tokenizer`: Converts logical expressions into standardized tokens.
- `Parser`: Builds an Abstract Syntax Tree (AST) using the Shunting Yard algorithm.
- `Evaluator`: Evaluates expressions dynamically based on a provided context.
- `Extensible` Design: Easy to add new operators or modify existing ones.
- `Support` for Nested Expressions: Handles complex, deeply nested logical formulas.

This system is ideal for educational purposes, logical analysis, or as a foundation for more advanced logical systems. It is implemented in PHP and follows an object-oriented design for clarity and maintainability.