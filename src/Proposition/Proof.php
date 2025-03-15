<?php

namespace Avicenna\Proposition;

use Avicenna\Parsers\LogicParser;
use Avicenna\Proposition\Expressions\LogicalExpression;
use Avicenna\TruthTable\TruthTableAnalyzer;
use Avicenna\TruthTable\TruthTableAnalyzerResult;

/**
 * Class Proof.
 *
 * Represents a single line (step) in a logical proof. It stores the original formula,
 * the parsed abstract syntax tree (AST) of the formula, references to other proof lines,
 * and the detected inference rule (if any). It also supports analyzing the proof using
 * truth table analysis.
 */
final class Proof
{
    /**
     * @var array REPLACEMENT_DETECTION_ORDER
     *            An ordered list of replacement rules to check first
     */
    private const REPLACEMENT_DETECTION_ORDER = [
        'Distributive', 'Commutative', 'Associativity', 'Exportation',
        'DoubleNegation', 'DeMorgan', 'Contraposition',
        'MaterialImplication', 'BiconditionalExchange',
        'Tautology',
    ];

    /**
     * @var array INFERENCE_DETECTION_ORDER
     *            An ordered list of inference rules to check after replacement rules
     */
    private const INFERENCE_DETECTION_ORDER = [
        'CPA', 'RAA', 'NegIntro',
        'MP', 'MT', 'MPT', 'HS', 'DS', 'CD',
        'DD', 'CI', 'ConjElim', 'DisjIntro',
        'DisjElim', 'Abs',
    ];

    /**
     * @var array REPLACEMENT_RULES_MAP
     *            Maps normalized short rule names to an array of possible user input variations
     *            for replacement rules
     */
    private const REPLACEMENT_RULES_MAP = [
        'Dist' => [
            'distributive', 'distribution', 'distr', 'dist',
        ],
        'Comm' => [
            'commutative', 'commutativity', 'comm',
        ],
        'Assoc' => [
            'associativity', 'associative', 'assoc',
        ],
        'Exp' => [
            'exportation', 'exp',
        ],
        'DN' => [
            'doublenegation', 'double negation', 'dn',
            'negationelimination', 'negelim',
        ],
        'DeM' => [
            'demorgan', 'de morgan', 'demorg', 'dem',
            'demorganlaw', 'demorgan\'s',
        ],
        'Contra' => [
            'contraposition', 'contrap', 'contra',
        ],
        'Simp' => [
            'materialimplication', 'impl', 'mi', 'mimp',
            'simplification', 'simp',
        ],
        'BE' => [
            'biconditionalexchange', 'biconditional', 'bce',
            'be',
        ],
        'T' => [
            'tautology', 'taut', 't',
        ],
    ];

    /**
     * @var array INFERENCE_RULES_MAP
     *            Maps normalized short rule names to an array of possible user input variations
     *            for inference rules
     */
    private const INFERENCE_RULES_MAP = [
        'Assume' => ['assume', 'as'],
        'CPA' => ['cpa'],
        'RAA' => ['raa', 'reductioadabsurdum'],
        '¬I' => ['negintro', '¬i', '~i', 'negationintroduction'],
        'MP' => ['mp', 'modusponens'],
        'MT' => ['mt', 'modustollens'],
        'MPT' => ['mpt', 'modusponendotollens'],
        'HS' => ['hs', 'hypotheticalsyllogism'],
        'DS' => ['ds', 'disjunctivesyllogism'],
        'CD' => ['cd', 'constructivedilemma'],
        'DD' => ['dd', 'destructivedilemma'],
        '∧I' => ['ci', 'conjunctionintroduction', '∧i'],
        '∧E' => ['conjelim', '∧e', 'conjunctionelimination'],
        '∨I' => ['disjintro', '∨i', 'disjunctionintroduction'],
        '∨E' => ['disjelim', '∨e', 'disjunctionelimination'],
        'ABS' => ['abs', 'absorption'],
    ];

    /**
     * @var array FULL_NAMES
     *            Maps normalized rule keys to their full descriptive names
     */
    private const FULL_NAMES = [
        // Replacement Rules
        'Dist' => 'Distributive',
        'Comm' => 'Commutative',
        'Assoc' => 'Associativity',
        'Exp' => 'Exportation',
        'DN' => 'Double Negation',
        'DeM' => 'De Morgan',
        'Contra' => 'Contraposition',
        'Simp' => 'Material Implication',
        'BE' => 'Biconditional Exchange',
        'T' => 'Tautology',

        // Inference Rules
        'CPA' => 'Conditional Proof Assumption',
        'RAA' => 'Reductio ad Absurdum',
        '¬I' => 'Negation Introduction',
        'MP' => 'Modus Ponens',
        'MT' => 'Modus Tollens',
        'MPT' => 'Modus Ponendo Tollens',
        'HS' => 'Hypothetical Syllogism',
        'DS' => 'Disjunctive Syllogism',
        'CD' => 'Constructive Dilemma',
        'DD' => 'Destructive Dilemma',
        '∧I' => 'Conjunction Introduction',
        '∧E' => 'Conjunction Elimination',
        '∨I' => 'Disjunction Introduction',
        '∨E' => 'Disjunction Elimination',
        'BE' => 'Biconditional Exchange',
    ];

    /**
     * The abstract syntax tree (AST) representation of the formula.
     */
    public ?LogicalExpression $ast = null;

    /**
     * Normalized rule of user which send for proof.
     */
    private ?string $userNormalizedRule = null;

    /**
     * Constructor.
     *
     * @param int         $line                   the line number of the proof step
     * @param string      $formula                the formula (propositional expression) for this step
     * @param array|null  $references             an array of reference line numbers on which this step is based
     * @param string|null $rule                   the user-provided rule for this step (if any)
     * @param string|null $detectedRule           the inference rule detected automatically for this step
     * @param string|null $autoType               The type of this step (e.g., 'Premise', 'Assume', or null).
     * @param array|null  $formulaNumberDirection An array of line numbers used in deduction (e.g., for subproofs).
     * @param int         $logicType              The logic type to use (e.g., LogicParser::PROPOSITIONAL).
     *
     * In the constructor, if the formula is not empty, it formats the formula's spacing,
     * parses it into an AST, and then updates the formula string based on the AST.
     */
    public function __construct(
        public int $line,
        public string $formula,
        public ?array $references = [],
        public ?string $rule = null,
        public ?string $detectedRule = null,
        public ?string $autoType = null, // ['Premise', 'Assume', null]
        public ?array $formulaNumberDirection = [],
        private int $logicType = LogicParser::PROPOSITIONAL
    ) {
        // If the formula is empty, do nothing.
        if (empty($formula)) {
            return;
        }

        // Format the spacing of the formula.
        $this->formula = static::formatSpacing($this->formula);

        // Parse the formula into an abstract syntax tree (AST).
        $this->ast = LogicParser::parseFormula($formula, $this->logicType);

        // Normalize the user-provided rule string and store it in the property.
        // (This line is executed in the class constructor.)
        $this->userNormalizedRule = static::normalizeRule($this->rule);

        // If the AST was successfully created, update the formula string to a standard form.
        if (null !== $this->ast) {
            $this->formula = static::expressionToString($this->ast);
        }
    }

    /**
     * Analyzes the current proof step using truth table analysis.
     *
     * @return TruthTableAnalyzerResult the result of the truth table analysis for this proof step
     */
    public function analyzeStep(): TruthTableAnalyzerResult
    {
        return TruthTableAnalyzer::analyze([$this]);
    }

    /**
     * Analyzes an array of proof steps using truth table analysis.
     *
     * @param Proof[] $proofs an array of Proof objects
     *
     * @return TruthTableAnalyzerResult the combined analysis result
     */
    public static function analyze(array $proofs): TruthTableAnalyzerResult
    {
        return TruthTableAnalyzer::analyze($proofs);
    }

    /**
     * Check user provided rule for proof is valid or not. we compare this with our detected rule.
     */
    public function checkUserRuleIsValid(): bool
    {
        if ('Assume' == $this->autoType || 'Premise' == $this->autoType) {
            return true;
        }

        return $this->userNormalizedRule == $this->detectedRule;
    }

    /**
     * Detects inference rules for this proof step based on all provided proofs.
     * The method checks first a set of replacement rules (e.g., Distributive, Commutative, etc.)
     * and then a set of inference rules (e.g., MP, MT, HS, etc.). When a rule is detected,
     * it is stored in the detectedRule property.
     *
     * @param Proof[] $allProofs an array of all Proof objects in the proof
     */
    public function detectRules(array $allProofs): void
    {
        // First, try replacement detection in a specified order.
        foreach (self::REPLACEMENT_DETECTION_ORDER as $rule) {
            $method = "detect{$rule}";
            if ($result = RuleDetector::$method($this, $allProofs)) {
                $this->detectedRule = $result;

                return;
            }
        }

        // Next, try inference detection in a specified order.
        foreach (self::INFERENCE_DETECTION_ORDER as $rule) {
            $method = "detect{$rule}";
            if ($result = RuleDetector::$method($this, $allProofs)) {
                $this->detectedRule = $result;

                return;
            }
        }
    }

    /**
     * Converts a LogicalExpression AST into a standardized string representation.
     *
     * @param LogicalExpression $expr the AST node representing a proposition
     *
     * @return string a standardized, human-readable string representation of the proposition
     */
    public static function expressionToString(LogicalExpression $expr): string
    {
        $raw = static::rawExpressionToString($expr);

        return static::formatSpacing($raw);
    }

    /**
     * Formats the spacing in a logical expression.
     *
     * - Removes extra spaces after the negation operator so that "¬ P" becomes "¬P".
     * - Normalizes spacing around binary operators (∧, ∨, →, ↔) so that exactly one space appears on each side.
     * - Removes extra spaces immediately after "(" and before ")".
     * - Trims leading and trailing whitespace.
     * - Removes one outer pair of redundant parentheses if the entire expression is enclosed.
     *
     * @param string $expression the input logical expression
     *
     * @return string the formatted expression
     */
    public static function formatSpacing(string $expression): string
    {
        // Remove any space after the negation operator, so that "¬ P" becomes "¬P"
        $expression = preg_replace('/¬\s+/u', LogicalExpression::OPERATOR_NEGATION, $expression);

        // Normalize binary operators (∧, ∨, →, ↔): ensure exactly one space on each side.
        $expression = preg_replace('/\s*(∧|∨|→|↔)\s*/u', ' $1 ', $expression);

        // Remove extra spaces immediately after "(" and immediately before ")"
        $expression = preg_replace(['/\\(\s+/u', '/\s+\\)/u'], ['(', ')'], $expression);

        // Trim leading and trailing spaces
        $expression = trim($expression);

        // Remove one pair of redundant outer parentheses if the entire expression is enclosed in them.
        return self::stripExtraOuterParens($expression);
    }

    /**
     * Normalizes a user input rule string.
     *
     * Converts the input to lowercase, then checks it against known variations for replacement rules first,
     * followed by inference rules. If a match is found, returns the normalized short rule key.
     *
     * @param string|null $input the user input rule string
     *
     * @return string|null The normalized rule key (e.g., 'MP', 'DN', etc.) if found, otherwise null.
     */
    public static function normalizeRule(?string $input): ?string
    {
        if (null === $input || empty($input)) {
            return null;
        }

        $input = strtolower($input);

        // Check replacement rules first
        foreach (self::REPLACEMENT_RULES_MAP as $shortName => $variations) {
            if (in_array($input, $variations)) {
                return $shortName;
            }
        }

        // Check inference rules
        foreach (self::INFERENCE_RULES_MAP as $shortName => $variations) {
            if (in_array($input, $variations)) {
                return $shortName;
            }
        }

        return null;
    }

    /**
     * Returns the full name for a given normalized rule key.
     *
     * @param string $rule The normalized rule key (e.g., 'MP', 'DN').
     *
     * @return string|null the full descriptive name of the rule, or null if the key is not found
     */
    public static function getRuleFullName(string $rule): ?string
    {
        return self::FULL_NAMES[$rule] ?? null;
    }

    /**
     * Strips one outer pair of redundant parentheses from the expression if the entire expression is enclosed.
     *
     * Example:
     *   Input: "((¬P ∨ ¬Q) ∨ ¬P)"  => Output: "(¬P ∨ ¬Q) ∨ ¬P"
     *   Input: "(((P → Q) → P) → P)" => Output: "((P → Q) → P) → P"
     *
     * @param string $expr the input expression
     *
     * @return string the expression with one level of outer redundant parentheses removed
     */
    private static function stripExtraOuterParens(string $expr): string
    {
        $expr = trim($expr);
        if (strlen($expr) < 2) {
            return $expr;
        }
        if ('(' === $expr[0] && ')' === substr($expr, -1)) {
            $balance = 0;
            $len = strlen($expr);
            $fullyEnclosed = true;
            for ($i = 0; $i < $len; ++$i) {
                if ('(' === $expr[$i]) {
                    ++$balance;
                } elseif (')' === $expr[$i]) {
                    --$balance;
                }
                // If the balance becomes zero before the end, the outer parentheses do not enclose the entire expression.
                if (0 === $balance && $i < $len - 1) {
                    $fullyEnclosed = false;

                    break;
                }
            }
            if ($fullyEnclosed && 0 === $balance) {
                // Remove the outer parentheses once.
                return trim(substr($expr, 1, -1));
            }
        }

        return $expr;
    }

    /**
     * Converts an AST of a proposition into its raw string representation.
     *
     * This is a recursive method that converts the abstract syntax tree into a string.
     *
     * @param LogicalExpression $expr the AST node representing a proposition
     *
     * @return string a raw string representation of the proposition
     */
    private static function rawExpressionToString(LogicalExpression $expr): string
    {
        return match (true) {
            LogicalExpression::isVariable($expr) => $expr->getName(),
            LogicalExpression::isNegation($expr) => LogicalExpression::OPERATOR_NEGATION.static::rawExpressionToString($expr->proposition),
            LogicalExpression::isAnd($expr) => '('.static::rawExpressionToString($expr->left).LogicalExpression::OPERATOR_CONJUNCTION.static::rawExpressionToString($expr->right).')',
            LogicalExpression::isOr($expr) => '('.static::rawExpressionToString($expr->left).LogicalExpression::OPERATOR_DISJUNCTION.static::rawExpressionToString($expr->right).')',
            LogicalExpression::isXor($expr) => '('.static::rawExpressionToString($expr->left).LogicalExpression::OPERATOR_XOR.static::rawExpressionToString($expr->right).')',
            LogicalExpression::isImplication($expr) => '('.static::rawExpressionToString($expr->left).LogicalExpression::OPERATOR_IMPLICATION.static::rawExpressionToString($expr->right).')',
            LogicalExpression::isBiconditional($expr) => '('.static::rawExpressionToString($expr->left).LogicalExpression::OPERATOR_BICONDITIONAL.static::rawExpressionToString($expr->right).')',
            default => '?'
        };
    }
}
