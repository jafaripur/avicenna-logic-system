# Avicenna Logic System

![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-brightgreen)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

![Dev](https://github.com/jafaripur/avicenna-logic-system/actions/workflows/dev.yml/badge.svg)
![Master](https://github.com/jafaripur/avicenna-logic-system/actions/workflows/master.yml/badge.svg)

A powerful tool for analyzing logical proofs and generating truth tables with propositional logic support and evaluator system.

This project is a comprehensive system for parsing and evaluating propositional logic expressions. It provides tools to tokenize, parse, and evaluate logical formulas, supporting standard logical operators such as AND (`∧`), OR (`∨`), NOT (`¬`), IMPLICATION (`→`), and BICONDITIONAL (`↔`). The system is designed to handle complex nested expressions and can evaluate them against a given context (a mapping of variables to boolean values).

## Key Features:
- `Tokenizer`: Converts logical expressions into standardized tokens.
- `Parser`: Builds an Abstract Syntax Tree (AST) using the Shunting Yard algorithm.
- `Evaluator`: Evaluates expressions dynamically based on a provided context.
- `Detect rule`: Detect replacement rules and inference rules.
- `Truth Table Generation`: Automatically generates truth tables for any propositional formula. Supports complex expressions with nested operators. Identifies tautologies, contradictions, and contingencies.
- `Lemmon-Style Natural Deduction`: Implements a Fitch-style natural deduction system. Supports step-by-step proof construction and validates rule applications (MP, MT, HS, etc.).
- `Extensible Design`: Easy to add new operators or modify existing ones.
- `Support for Nested Expressions`: Handles complex, deeply nested logical formulas.
- `Replacement rules`: Distributive, Commutative, Associativity, Exportation, DoubleNegation, DeMorgan, Contraposition, MaterialImplication, BiconditionalExchange, Tautology.
- `Inference rules`: Conditional Proof(CPA), Reductio ad Absurdum (RAA), NegIntro, Modus Ponens (MP), Modus Tollens (MT), Modus Ponendo Tollens (MPT), Hypothetical Syllogism (HS), Disjunctive Syllogism (DS), Constructive Dilemma (CD), Destructive Dilemma (DD), Conjunction Introduction (∧I), Conjunction Elimination (∧E), Disjunction Introduction (∨I), Disjunction Elimination (∨E), Absorption (Abs).
This system is ideal for educational purposes, logical analysis, or as a foundation for more advanced logical systems. It is implemented in PHP and follows an object-oriented design for clarity and maintainability.

## Installation

To get the Avicenna Logic System up and running, follow these steps:

### Prerequisites

- `PHP 8.3 or higher:` Ensure you have PHP 8.3 or a later version installed on your system. You can check your PHP version by running `php -v` in your terminal.
- `Composer:` Composer is a dependency manager for PHP. If you don't have it, download and install it from [getcomposer.org](https://getcomposer.org/).

### Installation Steps

1.  `Clone the Repository:` Clone the project repository to your local machine using Git:

```bash
git clone [repository-url]
cd [project-directory]
```

Replace `[repository-url]` with the actual URL of the repository and `[project-directory]` with the name of the folder where you want to clone the repository.

2.  `Install Dependencies:` Use Composer to install the project dependencies:

```bash
composer install --dev
```

This command will download and install all the necessary libraries specified in the `composer.json` file.


## Running Tests with PHPUnit

This project uses PHPUnit for automated testing. To run the tests, follow these steps:

1.  `Ensure PHPUnit is Installed:` If you installed dependencies using Composer (as described in the Installation section), PHPUnit should already be installed. You can verify this by running:

```bash
./vendor/bin/phpunit --version
```

If PHPUnit is not found, you can install it globally or as a project dependency using Composer.

2.  `Run the Tests:` From the project root directory, execute the following command:

```bash
./vendor/bin/phpunit
```

1.  `View the Results:` PHPUnit will display the test results in your terminal, showing the number of tests run, assertions made, and any failures or errors encountered.

```bash
PHPUnit 12.0.7 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.17
Configuration: /var/www/html/logic/phpunit.dist.xml

...........                                                       11 / 11 (100%)

Time: 00:00.027, Memory: 4.00 MB

OK (11 tests, 39 assertions)
```

## Usage Examples

### Evaluate Argument

Evaluates the proposition against the provided context.

```php
$input = "(p ∧ ¬(q → (r ↔ s))) → t";
$parser = LogicParser::parseFormula($input, LogicParser::PROPOSITIONAL);

$context = [
    'p' => true,
    'q' => false,
    'r' => true,
    's' => false,
    't' => true
];
$result = $parser->evaluate($context); // true
```

### Truth Table Generation

```php
// Example 1: Generate truth table for (p ∧ q) → r
$expression = new Implication(
    new AndExpression(new Variable('p'), new Variable('q')),
    new Variable('r')
);

$proof = new Proof(
    1, Proof::expressionToString($expression), null,
    null, null, null,
    [], LogicParser::PROPOSITIONAL
);

$result = TruthTableAnalyzer::analyze([$proof]);
TruthTablePrinter::print($result);

// Output:
//| p | q | r | p ∧ q | (p ∧ q) → r |
//+---+---+---+-------+-------------+
//| F | F | F | F     | T           |
//| F | F | T | F     | T           |
//| F | T | F | F     | T           |
//| F | T | T | F     | T           |
//| T | F | F | F     | T           |
//| T | F | T | F     | T           |
//| T | T | F | T     | F           |
//| T | T | T | T     | T           |
//+---+---+---+-------+-------------+
```

### Argument Parsing

```php
// Parse and validate a logical argument
$argument = "p ∧ (q ∨ r) ⊢ (p ∧ q) ∨ (p ∧ r)";
$parser = ArgumentsParser::create($argument);

// Get structured representation
$premises = $parser->getPremises();
$conclusion = $parser->getConclusion();

$input = $premises;
$input[] = $conclusion;

$result = TruthTableAnalyzer::analyze($input);

// Print in Lemmon style
Lemmon::getOutput($input);

```

### Lemmon-Style Proof

```php
$deduct = "
    [1]    (1) p ∧ (q ∨ r)          [Premise]
    [1]    (2) p                    [1, ∧E]
    [1]    (3) q ∨ r                [1, ∧E]
    [4]    (4) q                    [Assume]
    [1,4]  (5) p ∧ q                [2, 4, ∧I]
    [1,4]  (6) (p ∧ q) ∨ (p ∧ r)    [5, ∨I]
    [7]    (7) r                    [Assume]
    [1,7]  (8) p ∧ r                [2, 7, ∧I]
    [1,7]  (9) (p ∧ q) ∨ (p ∧ r)    [8, ∨I]
    [1]    (10) (p∧q) ∨ (p ∧ r)     [3, 4, 6, 7, 9, ∨e]
";
$proofs = Lemmon::parseInput($deduct, LogicParser::PROPOSITIONAL);

// Print in lemmon style
Lemmon::getOutput($proofs);

// Analyze argument
$truthTableData = Proof::analyze($proofs);

// Print truth table
TruthTablePrinter::print($truthTableData);
```

## Contributing

We welcome contributions to the Avicenna Logic System! Here's how you can help:

### Getting Started

1.  `Fork the Repository:` Start by forking the repository to your own GitHub account.
2.  `Clone Your Fork:` Clone your forked repository to your local machine:
```bash
git clone [repository-url] -b dev avicenna-logic
cd [avicenna-logic]
```
3.  `Create a Branch:` Create a new branch for your feature or bug fix:
```bash
git checkout -b feature/your-feature-name dev
# or
git checkout -b fix/issue-number-or-description dev
```
4.  `Make Your Changes:` Implement your feature or bug fix.
5.  `Commit Your Changes:` Commit your changes with clear and descriptive commit messages:
```bash
git add .
git commit -m "Add your feature or fix description"
```
6.  `Push Your Changes:` Push your branch to your forked repository:
```bash
git push origin feature/your-feature-name
```

### Submitting a Pull Request

1.  `Create a Pull Request:` Go to your forked repository on GitHub and create a new pull request.
2.  `Target the dev Branch:` `Important`: Make sure your pull request targets the `dev` branch.
3.  `Describe Your Changes:` Provide a clear and detailed description of your changes in the pull request.
4.  `Wait for Review:` The project maintainers will review your pull request. Be prepared to address any feedback or make necessary changes.

### Branching Strategy

- `master:` This branch contains the stable, production-ready code.
- `dev:` This branch is used for development and integration of new features and bug fixes.
- `Feature/Issue Branches:` All new features and bug fixes should be implemented in separate branches that are then merged into `dev`.

### Merging to `master`

- The project maintainers or owners will handle merging the `dev` branch into the `master` branch after thorough testing and review.

### Coding Standards

- Please follow the existing coding style and conventions used in the project.
- Write clear and concise code with appropriate comments.
- Ensure your changes include necessary tests.
- Use `./lint.sh` in root of the project for checking systanx and code style and fix it.

### Reporting Issues

- If you find a bug or have a feature request, please open an issue on GitHub.
- Provide a clear and detailed description of the issue or feature.

### Thank You!

Thank you for your interest in contributing to the Avicenna Logic System. Your contributions are highly valued!

## References

- An Introduction to New Logic, `Zia Movahed`
- A symbolic introduction to propositional logic, `Mortaza Hajihosseini`
- [Open Logic Project](https://openlogicproject.org)
- [forall x: Calgary, An Introduction to Formal Logic](https://forallx.openlogicproject.org/)