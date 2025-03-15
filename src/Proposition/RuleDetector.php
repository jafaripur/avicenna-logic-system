<?php

namespace Avicenna\Proposition;

use Avicenna\Proposition\Expressions\And;
use Avicenna\Proposition\Expressions\AndExpression;
use Avicenna\Proposition\Expressions\Biconditional;
use Avicenna\Proposition\Expressions\Implication;
use Avicenna\Proposition\Expressions\LogicalExpression;
use Avicenna\Proposition\Expressions\Negation;
use Avicenna\Proposition\Expressions\Or;
use Avicenna\Proposition\Expressions\OrExpression;

/**
 * Detects logical inference rules and properties in formal proofs.
 *
 * This class provides static methods to analyze proof steps and identify applied logical rules
 * by comparing current proof lines with referenced previous lines. It supports detection of
 * both equivalence rules and inference rules from propositional logic.
 */
final class RuleDetector
{
    /**
     * Returns the inner proposition of a negation.
     *
     * @param Negation $prop the negation proposition
     *
     * @return LogicalExpression the proposition that is being negated
     */
    public static function getNotLogicalExpression(Negation $prop): LogicalExpression
    {
        return $prop->proposition;
    }

    /**
     * Helper method: Extracts antecedent and consequent from implication.
     *
     * @param Implication $implies Implication proposition
     *
     * @return array With 'antecedent' and 'consequent' keys
     */
    public static function getImplicationParts(Implication $prop): array
    {
        return ['antecedent' => $prop->left, 'consequent' => $prop->right];
    }

    /**
     * Detects distributive law applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'Dist' if distributive law is detected, null otherwise
     *
     * Checks four distributive law patterns:
     * 1. (A ∨ B) ∧ (A ∨ C) ⇔ A ∨ (B ∧ C)
     * 2. A ∧ (B ∨ C) ⇔ (A ∧ B) ∨ (A ∧ C)
     * 3. (A ∧ B) ∨ (A ∧ C) ⇔ A ∧ (B ∨ C)
     * 4. A ∨ (B ∧ C) ⇔ (A ∨ B) ∧ (A ∨ C)
     */
    public static function detectDistributive(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $currentProp = $currentLine->ast;
        $refProp = $ref->ast;

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // 4: (A ∨ B) ∧ (A ∨ C) ↔ A ∨ (B ∧ C)
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        if (LogicalExpression::isAnd($refProp)) {
            $leftOr = $refProp->left;
            $rightOr = $refProp->right;

            if (LogicalExpression::isOr($leftOr) && LogicalExpression::isOr($rightOr)) {
                // Finding A in any position of ORs (left or right)
                $A = null;
                $B = null;
                $C = null;

                // Check all possible combinations for position A
                if (LogicalExpressionComparator::areEqual($leftOr->left, $rightOr->left)) {
                    $A = $leftOr->left;
                    $B = $leftOr->right;
                    $C = $rightOr->right;
                } elseif (LogicalExpressionComparator::areEqual($leftOr->left, $rightOr->right)) {
                    $A = $leftOr->left;
                    $B = $leftOr->right;
                    $C = $rightOr->left;
                } elseif (LogicalExpressionComparator::areEqual($leftOr->right, $rightOr->left)) {
                    $A = $leftOr->right;
                    $B = $leftOr->left;
                    $C = $rightOr->right;
                } elseif (LogicalExpressionComparator::areEqual($leftOr->right, $rightOr->right)) {
                    $A = $leftOr->right;
                    $B = $leftOr->left;
                    $C = $rightOr->left;
                }

                if (null !== $A) {
                    $expected = new OrExpression(
                        clone $A,
                        new AndExpression(clone $B, clone $C)
                    );

                    return LogicalExpressionComparator::areEqual($currentProp, $expected) ? 'Dist' : null;
                }
            }
        }

        /**
         * @var AndExpression $refProp
         */
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // 2: A ∧ (B ∨ C) ↔ (A ∧ B) ∨ (A ∧ C)
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        if (LogicalExpression::isAnd($refProp) && LogicalExpression::isOr($refProp->right)) {
            $A = $refProp->left;
            $B = $refProp->right->left;
            $C = $refProp->right->right;

            $expected = new OrExpression(
                new AndExpression(clone $A, clone $B),
                new AndExpression(clone $A, clone $C)
            );

            return LogicalExpressionComparator::areEqual($currentProp, $expected) ? 'Dist' : null;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // 2: (A ∧ B) ∨ (A ∧ C) ↔ A ∧ (B ∨ C) (Reverse of 1)
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        if (LogicalExpression::isOr($refProp)) {
            $leftAnd = $refProp->left;
            $rightAnd = $refProp->right;

            if (LogicalExpression::isAnd($leftAnd) && LogicalExpression::isAnd($rightAnd)) {
                $A1 = $leftAnd->left;
                $B = $leftAnd->right;
                $A2 = $rightAnd->left;
                $C = $rightAnd->right;

                if (LogicalExpressionComparator::areEqual($A1, $A2)) {
                    $expected = new AndExpression(
                        clone $A1,
                        new OrExpression(clone $B, clone $C)
                    );

                    return LogicalExpressionComparator::areEqual($currentProp, $expected) ? 'Dist' : null;
                }
            }
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        // 3: A ∨ (B ∧ C) ↔ (A ∨ B) ∧ (A ∨ C)
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        if (LogicalExpression::isOr($refProp) && LogicalExpression::isAnd($refProp->right)) {
            $A = $refProp->left;
            $B = $refProp->right->left;
            $C = $refProp->right->right;

            $expected = new AndExpression(
                new OrExpression(clone $A, clone $B),
                new OrExpression(clone $A, clone $C)
            );

            return LogicalExpressionComparator::areEqual($currentProp, $expected) ? 'Dist' : null;
        }

        return null;
    }

    /**
     * Detects associative law applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'Assoc' if associative law is detected, null otherwise
     *
     * Verifies associativity by comparing component equality regardless of grouping order
     * Works for both conjunction (∧) and disjunction (∨) operators
     */
    public static function detectAssociativity(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $currentProp = $currentLine->ast;
        $refProp = $ref->ast;

        if (!LogicalExpression::isOr($currentProp) && !LogicalExpression::isAnd($currentProp)) {
            return null;
        }
        if (get_class($currentProp) !== get_class($refProp)) {
            return null;
        }

        // Function to extract all nested components
        $extractParts = function (LogicalExpression $prop) use (&$extractParts): array {
            $parts = [];
            if (LogicalExpression::isOr($prop) || LogicalExpression::isAnd($prop)) {
                $parts = array_merge(
                    $extractParts($prop->left),
                    $extractParts($prop->right)
                );
            } else {
                $parts[] = $prop;
            }

            return $parts;
        };

        $currentParts = $extractParts($currentProp);
        $refParts = $extractParts($refProp);

        // Check for equality of components regardless of order
        $currentSorted = static::sortLogicalExpressions($currentParts);
        $refSorted = static::sortLogicalExpressions($refParts);

        return $currentSorted == $refSorted ? 'Assoc' : null;
    }

    /**
     * Detects commutative law applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'Comm' if commutative law is detected, null otherwise
     *
     * Checks operand order reversal for conjunction (∧) and disjunction (∨) operators
     */
    public static function detectCommutative(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $currentProp = $currentLine->ast;
        $refProp = $ref->ast;

        if (!LogicalExpression::isOr($refProp) && !LogicalExpression::isAnd($refProp)) {
            return null;
        }

        $reversedProp = new $refProp($refProp->right, $refProp->left);

        return LogicalExpressionComparator::areEqual($currentProp, $reversedProp) ? 'Comm' : null;
    }

    /**
     * Detects double negation rules.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'DN' if double negation rule is detected, null otherwise
     *
     * Handles both directions:
     * 1. ¬¬A ⇒ A (Double negation elimination)
     * 2. A ⇒ ¬¬A (Double negation introduction)
     */
    public static function detectDoubleNegation(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        // 1: ¬¬A → A
        if (LogicalExpression::isNegation($ref->ast) && LogicalExpression::isNegation($ref->ast->proposition)) {
            return LogicalExpressionComparator::areEqual($currentLine->ast, $ref->ast->proposition->proposition) ? 'DN' : null;
        }

        // 2: A → ¬¬A
        $doubleNeg = new Negation(new Negation($ref->ast));

        return LogicalExpressionComparator::areEqual($currentLine->ast, $doubleNeg) ? 'DN' : null;
    }

    /**
     * Detects De Morgan's law applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'DeM' if De Morgan's law is detected, null otherwise
     *
     * Checks both forms:
     * 1. ¬(A ∨ B) ⇔ ¬A ∧ ¬B
     * 2. ¬(A ∧ B) ⇔ ¬A ∨ ¬B
     * Works in both directions (equivalence detection)
     */
    public static function detectDeMorgan(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $currentAst = $currentLine->ast;
        $refAst = $ref->ast;

        // 1: ¬(A ∨ B) → ¬A ∧ ¬B
        if (LogicalExpression::isNegation($refAst) && LogicalExpression::isOr($refAst->proposition)) {
            $expected = new AndExpression(new Negation($refAst->proposition->left), new Negation($refAst->proposition->right));

            return LogicalExpressionComparator::areEqual($currentAst, $expected) ? 'DeM' : null;
        }

        // 2: ¬A ∧ ¬B → ¬(A ∨ B)
        if (LogicalExpression::isNegation($currentAst) && LogicalExpression::isOr($currentAst->proposition)) {
            $expected = new AndExpression(new Negation($currentAst->proposition->left), new Negation($currentAst->proposition->right));

            return LogicalExpressionComparator::areEqual($refAst, $expected) ? 'DeM' : null;
        }

        return null;
    }

    /**
     * Detects material implication conversions.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'Simp' if material implication is detected, null otherwise
     *
     * Converts between implication and disjunction forms:
     * 1. A → B ⇔ ¬A ∨ B
     * 2. ¬A ∨ B ⇔ A → B
     */
    public static function detectMaterialImplication(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $currentAst = $currentLine->ast;
        $refAst = $ref->ast;

        // Convert A → B to ¬A ∨ B
        if (LogicalExpression::isImplication($refAst)) {
            /**
             * @var Implication $refAst
             */
            $expected = new OrExpression(new Negation($refAst->left), $refAst->right);

            return LogicalExpressionComparator::areEqual($currentAst, $expected) ? 'Simp' : null;
        }

        // Convert ¬A ∨ B to A → B
        if (LogicalExpression::isImplication($currentAst)) {
            /**
             * @var Implication $currentAst
             */
            $expected = new OrExpression(new Negation($currentAst->left), $currentAst->right);

            return LogicalExpressionComparator::areEqual($refAst, $expected) ? 'Simp' : null;
        }

        return null;
    }

    /**
     * Detects contraposition rule applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'Contra' if contraposition is detected, null otherwise
     *
     * Verifies contrapositive equivalence:
     * A → B ⇔ ¬B → ¬A
     */
    public static function detectContraposition(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref || !LogicalExpression::isImplication($ref->ast)) {
            return null;
        }

        $currentImplication = $currentLine->ast;
        $refImplication = $ref->ast;

        /**
         * @var Implication $currentImplication
         * @var Implication $refImplication
         */
        // Checking the two directions of Contraposition
        $contra1 = new Implication(new Negation($refImplication->right), new Negation($refImplication->left));
        $contra2 = new Implication(new Negation($currentImplication->right), new Negation($currentImplication->left));

        return (LogicalExpressionComparator::areEqual($currentImplication, $contra1)
                || LogicalExpressionComparator::areEqual($refImplication, $contra2)) ? 'Contra' : null;
    }

    /**
     * Detects exportation rule applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'Exp' if exportation is detected, null otherwise
     *
     * Handles nested implication transformations:
     * 1. (A ∧ B) → C ⇔ A → (B → C)
     * 2. A → (B → C) ⇔ (A ∧ B) → C
     */
    public static function detectExportation(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $currentProp = $currentLine->ast;
        $refProp = $ref->ast;

        /**
         * @var Implication $refProp
         */
        // 1:  (A ∧ B) → C به A → (B → C)
        if (LogicalExpression::isImplication($refProp) && LogicalExpression::isAnd($refProp->left)) {
            $A = $refProp->left->left;
            $B = $refProp->left->right;
            $C = $refProp->right;

            $expected = new Implication($A, new Implication($B, $C));

            return LogicalExpressionComparator::areEqual($currentProp, $expected) ? 'Exp' : null;
        }

        /**
         * @var Implication $refProp
         */
        // 2:  A → (B → C) به (A ∧ B) → C
        if (LogicalExpression::isImplication($refProp) && LogicalExpression::isImplication($refProp->right)) {
            $A = $refProp->left;
            $B = $refProp->right->left;
            $C = $refProp->right->right;

            $expected = new Implication(new AndExpression($A, $B), $C);

            return LogicalExpressionComparator::areEqual($currentProp, $expected) ? 'Exp' : null;
        }

        return null;
    }

    /**
     * Detects tautological simplifications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'T' if tautology is detected, null otherwise
     *
     * Identifies redundant logical equivalences:
     * 1. A ∧ A ⇔ A
     * 2. A ∨ A ⇔ A
     */
    public static function detectTautology(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $isValid = (
            (LogicalExpression::isAnd($ref->ast) || LogicalExpression::isOr($ref->ast))
            && LogicalExpressionComparator::areEqual($ref->ast->left, $ref->ast->right)
            && LogicalExpressionComparator::areEqual($currentLine->ast, $ref->ast->left)
        );

        return $isValid ? 'T' : null;
    }

    /**
     * Detects biconditional equivalences.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'BE' if biconditional exchange is detected, null otherwise
     *
     * Converts between biconditional and conjunction of conditionals:
     * P ↔ Q ⇔ (P → Q) ∧ (Q → P)
     */
    public static function detectBiconditionalExchange(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref) {
            return null;
        }

        $currentProp = $currentLine->ast;
        $refProp = $ref->ast;

        // P ↔ Q ↔ (P → Q) ∧ (Q → P)
        if (LogicalExpression::isBiconditional($refProp)) {
            $expected = new AndExpression(
                new Implication($refProp->left, $refProp->right),
                new Implication($refProp->right, $refProp->left)
            );

            return LogicalExpressionComparator::areEqual($currentProp, $expected) ? 'BE' : null;
        }

        // (Q → P) ∧ (P → Q) ↔ P ↔ Q
        if (LogicalExpression::isBiconditional($currentProp) && LogicalExpression::isAnd($refProp)) {
            /**
             * @var AndExpression $refProp
             */
            $valid = (
                LogicalExpression::isImplication($refProp->left)
                && LogicalExpression::isImplication($refProp->right)
                && LogicalExpressionComparator::areEqual($refProp->left->right, $refProp->right->left)
                && LogicalExpressionComparator::areEqual($refProp->right->right, $refProp->left->left)
            );

            return $valid ? 'BE' : null;
        }

        return null;
    }

    /**
     * Detects Modus Ponens (MP) applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'MP' if Modus Ponens is detected, null otherwise
     *
     * Validates MP rule application:
     * Given A → B and A, conclude B
     */
    public static function detectMP(Proof $currentLine, array $allProofs): ?string
    {
        if (2 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref1 = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $ref2 = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        // Check every possible combination of references
        /**
         * @var Proof $impl
         */
        foreach ([[$ref1, $ref2], [$ref2, $ref1]] as [$impl, $antecedent]) {
            if (LogicalExpression::isImplication($impl->ast)) {
                $parts = static::getImplicationParts($impl->ast);
                if (LogicalExpressionComparator::areEqual($parts['antecedent'], $antecedent->ast)
                    && LogicalExpressionComparator::areEqual($parts['consequent'], $currentLine->ast)) {
                    return 'MP';
                }
            }
        }

        return null;
    }

    /**
     * Detects Modus Tollens (MT) applications.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   All proofs in the current derivation
     *
     * @return string|null 'MT' if Modus Tollens is detected, null otherwise
     *
     * Validates MT rule application:
     * Given A → B and ¬B, conclude ¬A
     */
    public static function detectMT(Proof $currentLine, array $allProofs): ?string
    {
        if (2 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref1 = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $ref2 = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        /**
         * @var Proof $impl
         */
        foreach ([[$ref1, $ref2], [$ref2, $ref1]] as [$impl, $negConsequent]) {
            if (LogicalExpression::isImplication($impl->ast)) {
                $parts = static::getImplicationParts($impl->ast);
                if (LogicalExpression::isNegation($currentLine->ast)
                    && LogicalExpression::isNegation($negConsequent->ast)) {
                    $currentProp = static::getNotLogicalExpression($currentLine->ast);
                    $negConsProp = static::getNotLogicalExpression($negConsequent->ast);
                    if (LogicalExpressionComparator::areEqual($parts['consequent'], $negConsProp)
                        && LogicalExpressionComparator::areEqual($parts['antecedent'], $currentProp)) {
                        return 'MT';
                    }
                }
            }
        }

        return null;
    }

    /**
     * Detects Modus Ponendo Tollens (MPT) rule application.
     *
     * Validates inference from a disjunction and negation of one disjunct
     * to conclude the other disjunct. Checks both possible orderings.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   Array of all proof lines in the derivation
     *
     * @return string|null 'MPT' if rule is applied correctly, null otherwise
     */
    public static function detectMPT(Proof $currentLine, array $allProofs): ?string
    {
        if (2 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref1 = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $ref2 = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        // Check possible combinations
        foreach ([[$ref1, $ref2], [$ref2, $ref1]] as [$disj, $negation]) {
            if (
                LogicalExpression::isOr($disj->ast)
                && LogicalExpression::isNegation($negation->ast)
            ) {
                $disjParts = ['left' => $disj->ast->left, 'right' => $disj->ast->right];

                // Check for a conflict match with one of the components
                if (LogicalExpressionComparator::areEqual($negation->ast->proposition, $disjParts['left'])) {
                    return LogicalExpressionComparator::areEqual($currentLine->ast, $disjParts['right']) ? 'MPT' : null;
                }
                if (LogicalExpressionComparator::areEqual($negation->ast->proposition, $disjParts['right'])) {
                    return LogicalExpressionComparator::areEqual($currentLine->ast, $disjParts['left']) ? 'MPT' : null;
                }
            }
        }

        return null;
    }

    /**
     * Detects Hypothetical Syllogism (HS) rule application.
     *
     * Validates chaining of two conditional statements where
     * the consequent of the first matches the antecedent of the second.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   Array of all proof lines in the derivation
     *
     * @return string|null 'HS' if rule is applied correctly, null otherwise
     */
    public static function detectHS(Proof $currentLine, array $allProofs): ?string
    {
        if (2 !== count($currentLine->formulaNumberDirection) || !LogicalExpression::isImplication($currentLine->ast)) {
            return null;
        }

        $ref1 = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $ref2 = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        // Check if both references are  Implication
        if ($ref1 && $ref2 && LogicalExpression::isImplication($ref1->ast) && LogicalExpression::isImplication($ref2->ast)) {
            $parts1 = static::getImplicationParts($ref1->ast);
            $parts2 = static::getImplicationParts($ref2->ast);
            $currentParts = static::getImplicationParts($currentLine->ast);

            // Check for chaining
            if (
                LogicalExpressionComparator::areEqual($parts1['consequent'], $parts2['antecedent'])
                && LogicalExpressionComparator::areEqual($parts1['antecedent'], $currentParts['antecedent'])
                && LogicalExpressionComparator::areEqual($parts2['consequent'], $currentParts['consequent'])
            ) {
                return 'HS';
            }
        }

        return null;
    }

    /**
     * Detects Disjunctive Syllogism (DS) rule application.
     *
     * Validates inference from a disjunction and negation of one disjunct
     * to conclude the remaining disjunct. Checks all component orderings.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   Array of all proof lines in the derivation
     *
     * @return string|null 'DS' if rule is applied correctly, null otherwise
     */
    public static function detectDS(Proof $currentLine, array $allProofs): ?string
    {
        if (2 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref1 = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $ref2 = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        foreach ([[$ref1, $ref2], [$ref2, $ref1]] as [$disjunction, $negation]) {
            if (LogicalExpression::isOr($disjunction->ast) && LogicalExpression::isNegation($negation->ast)) {
                $disjParts = ['left' => $disjunction->ast->left, 'right' => $disjunction->ast->right];
                $negProp = $negation->ast->proposition;

                // Check for a conflict match with one of the chapter components
                foreach (['left', 'right'] as $side) {
                    if (LogicalExpressionComparator::areEqual($disjParts[$side], $negProp)) {
                        $otherSide = 'left' === $side ? $disjParts['right'] : $disjParts['left'];
                        if (LogicalExpressionComparator::areEqual($otherSide, $currentLine->ast)) {
                            return 'DS';
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Detects Conjunction Introduction (∧I) rule application.
     *
     * Verifies that the current line's conjunction is properly derived from two premises.
     * Checks both possible orderings of the conjuncts.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   Array of all proof lines in the derivation
     *
     * @return string|null '∧I' if rule is applied correctly, null otherwise
     */
    public static function detectCI(Proof $currentLine, array $allProofs): ?string
    {
        if (
            !LogicalExpression::isAnd($currentLine->ast)
            || 2 !== count($currentLine->formulaNumberDirection)
        ) {
            return null;
        }

        $lineA = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $lineB = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        $expectedLeft = $currentLine->ast->left;
        $expectedRight = $currentLine->ast->right;

        $valid = (
            LogicalExpressionComparator::areEqual($lineA->ast, $expectedLeft)
            && LogicalExpressionComparator::areEqual($lineB->ast, $expectedRight)
        ) || (
            LogicalExpressionComparator::areEqual($lineA->ast, $expectedRight)
            && LogicalExpressionComparator::areEqual($lineB->ast, $expectedLeft)
        );

        return $valid ? '∧I' : null;
    }

    /**
     * Detects Conjunction Elimination (∧E) rule application.
     *
     * Validates that the current line is one of the conjuncts from a previous conjunction.
     * Supports elimination from both left and right conjuncts.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   Array of all proof lines in the derivation
     *
     * @return string|null '∧E' if rule is applied correctly, null otherwise
     */
    public static function detectConjElim(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref || !LogicalExpression::isAnd($ref->ast)) {
            return null;
        }

        $conjParts = ['left' => $ref->ast->left, 'right' => $ref->ast->right];

        return (LogicalExpressionComparator::areEqual($currentLine->ast, $conjParts['left'])
                || LogicalExpressionComparator::areEqual($currentLine->ast, $conjParts['right']))
            ? '∧E'
            : null;
    }

    /**
     * Detects Disjunction Introduction (∨I) rule application.
     *
     * Verifies the current disjunction contains a previously established proposition
     * as either its left or right disjunct.
     *
     * @param Proof $currentLine The current proof line being analyzed
     * @param array $allProofs   Array of all proof lines in the derivation
     *
     * @return string|null '∨I' if rule is applied correctly, null otherwise
     */
    public static function detectDisjIntro(Proof $currentLine, array $allProofs): ?string
    {
        if (!LogicalExpression::isOr($currentLine->ast) || 1 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $disjParts = ['left' => $currentLine->ast->left, 'right' => $currentLine->ast->right];

        return (LogicalExpressionComparator::areEqual($ref->ast, $disjParts['left'])
                || LogicalExpressionComparator::areEqual($ref->ast, $disjParts['right']))
            ? '∨I'
            : null;
    }

    /**
     * Detects Disjunction Elimination (∨E) in a proof step.
     *
     * Expected structure: the formulaNumberDirection array should have exactly 5 elements:
     * [disjunctionLine, assumeA, conclusion1, assumeB, conclusion2],
     * where the current line's AST is a disjunction.
     *
     * It retrieves the referenced proofs by their line numbers, validates that the disjunction
     * and assumptions match the expected structure, and then checks whether the conclusions
     * match the current line's AST.
     *
     * @param Proof   $currentLine the current proof step
     * @param Proof[] $allProofs   an array of all Proof objects
     *
     * @return string|null returns '∨E' if Disjunction Elimination is detected; otherwise, null
     */
    public static function detectDisjElim(Proof $currentLine, array $allProofs): ?string
    {
        // Check that there are exactly 5 entries in formulaNumberDirection and the current AST is an OrExpression.
        if (
            5 !== count($currentLine->formulaNumberDirection) // [disj, A, C1, B, C2]
            || !LogicalExpression::isOr($currentLine->ast)
        ) {
            return null;
        }

        // Retrieve the corresponding proofs by line numbers from formulaNumberDirection.
        $disjLine = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $assumeA = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);
        $conclusion1 = static::findProofByLine($currentLine->formulaNumberDirection[2], $allProofs);
        $assumeB = static::findProofByLine($currentLine->formulaNumberDirection[3], $allProofs);
        $conclusion2 = static::findProofByLine($currentLine->formulaNumberDirection[4], $allProofs);

        // Initial validation: disjunctionLine must be an OrExpression,
        // assumptions must be marked as 'Assume', and both conclusions must equal currentLine's AST.
        if (
            !$disjLine || !LogicalExpression::isOr($disjLine->ast)
            || 'Assume' !== $assumeA->autoType
            || 'Assume' !== $assumeB->autoType
            || !LogicalExpressionComparator::areEqual($conclusion1->ast, $currentLine->ast)
            || !LogicalExpressionComparator::areEqual($conclusion2->ast, $currentLine->ast)
        ) {
            return null;
        }

        // Extract disjunction parts.
        $disjParts = ['left' => $disjLine->ast->left, 'right' => $disjLine->ast->right];
        $validAssumptions = (
            LogicalExpressionComparator::areEqual($assumeA->ast, $disjParts['left'])
            && LogicalExpressionComparator::areEqual($assumeB->ast, $disjParts['right'])
        ) || (
            LogicalExpressionComparator::areEqual($assumeA->ast, $disjParts['right'])
            && LogicalExpressionComparator::areEqual($assumeB->ast, $disjParts['left'])
        );

        return $validAssumptions ? '∨E' : null;
    }

    /**
     * Detects Constructive Dilemma (CD) in a proof step.
     *
     * Expected structure: formulaNumberDirection should contain 3 line numbers, with the current AST being a disjunction.
     * The referenced proofs should include two implications and one disjunction.
     *
     * It then compares the antecedents of the implications with the disjunction parts and checks if
     * the current line's AST matches the expected result when combining the consequents.
     *
     * @param Proof   $currentLine the current proof step
     * @param Proof[] $allProofs   an array of all Proof objects
     *
     * @return string|null returns 'CD' if Constructive Dilemma is detected; otherwise, null
     */
    public static function detectCD(Proof $currentLine, array $allProofs): ?string
    {
        if (
            3 !== count($currentLine->formulaNumberDirection)
            || !LogicalExpression::isOr($currentLine->ast)
        ) {
            return null;
        }

        // Retrieve the proofs indicated by formulaNumberDirection.
        $refs = array_map(fn ($l) => static::findProofByLine($l, $allProofs), $currentLine->formulaNumberDirection);

        // Separate proofs into implications and disjunction.
        $implications = [];
        $disjunction = null;
        foreach ($refs as $ref) {
            if (LogicalExpression::isImplication($ref->ast)) {
                $implications[] = $ref->ast;
            } elseif (LogicalExpression::isOr($ref->ast)) {
                $disjunction = $ref->ast;
            }
        }

        if (2 !== count($implications) || !$disjunction) {
            return null;
        }

        // Extract parts of the two implications.
        $impl1Parts = static::getImplicationParts($implications[0]);
        $impl2Parts = static::getImplicationParts($implications[1]);
        $disjParts = ['left' => $disjunction->left, 'right' => $disjunction->right];

        // Check for a valid match in either order.
        $valid = (
            (
                LogicalExpressionComparator::areEqual($impl1Parts['antecedent'], $disjParts['left'])
                && LogicalExpressionComparator::areEqual($impl2Parts['antecedent'], $disjParts['right'])
                && LogicalExpressionComparator::areEqual($currentLine->ast->left, $impl1Parts['consequent'])
                && LogicalExpressionComparator::areEqual($currentLine->ast->right, $impl2Parts['consequent'])
            ) || (
                LogicalExpressionComparator::areEqual($impl1Parts['antecedent'], $disjParts['right'])
                && LogicalExpressionComparator::areEqual($impl2Parts['antecedent'], $disjParts['left'])
                && LogicalExpressionComparator::areEqual($currentLine->ast->left, $impl2Parts['consequent'])
                && LogicalExpressionComparator::areEqual($currentLine->ast->right, $impl1Parts['consequent'])
            )
        );

        return $valid ? 'CD' : null;
    }

    /**
     * Detects Destructive Dilemma (DD) in a proof step.
     *
     * Expected structure: formulaNumberDirection should contain exactly 2 line numbers and the current AST must be a disjunction.
     *
     * The method attempts to find a pair where one reference is a conjunction (composed of two implications)
     * and the other is a disjunction. It then checks if the negations of the consequences of the implications
     * match the components of the disjunction and if the negation of the antecedents matches the current AST.
     *
     * @param Proof   $currentLine the current proof step
     * @param Proof[] $allProofs   an array of all Proof objects
     *
     * @return string|null returns 'DD' if Destructive Dilemma is detected; otherwise, null
     */
    public static function detectDD(Proof $currentLine, array $allProofs): ?string
    {
        if (2 !== count($currentLine->formulaNumberDirection) || !LogicalExpression::isOr($currentLine->ast)) {
            return null;
        }

        $ref1 = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        $ref2 = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        foreach ([[$ref1, $ref2], [$ref2, $ref1]] as [$conj, $negDisj]) {
            if (LogicalExpression::isAnd($conj->ast)
                && LogicalExpression::isOr($negDisj->ast)) {
                $conjLeft = $conj->ast->left;
                $conjRight = $conj->ast->right;
                if (!(LogicalExpression::isImplication($conjLeft)) || !(LogicalExpression::isImplication($conjRight))) {
                    continue;
                }

                // Extracting the implications and disjunction components of contradictions
                $impl1Parts = static::getImplicationParts($conjLeft);
                $impl2Parts = static::getImplicationParts($conjRight);
                $negDisjParts = ['left' => $negDisj->ast->left, 'right' => $negDisj->ast->right];

                // Check for consistency between consequences and result
                $valid = (
                    LogicalExpressionComparator::areEqual($negDisjParts['left'], new Negation($impl1Parts['consequent']))
                    && LogicalExpressionComparator::areEqual($negDisjParts['right'], new Negation($impl2Parts['consequent']))
                    && LogicalExpressionComparator::areEqual($currentLine->ast->left, new Negation($impl1Parts['antecedent']))
                    && LogicalExpressionComparator::areEqual($currentLine->ast->right, new Negation($impl2Parts['antecedent']))
                );

                if ($valid) {
                    return 'DD';
                }
            }
        }

        return null;
    }

    /**
     * Detects Absorption (Abs) rule in a proof step.
     *
     * Expected structure: formulaNumberDirection must contain exactly one line number,
     * and the current AST must be an implication.
     *
     * The method compares the current implication with a referenced implication
     * to check if it has the form A → (A ∧ B), which is the Absorption rule.
     *
     * @param Proof   $currentLine the current proof step
     * @param Proof[] $allProofs   an array of all Proof objects
     *
     * @return string|null returns 'Abs' if Absorption is detected; otherwise, null
     */
    public static function detectAbs(Proof $currentLine, array $allProofs): ?string
    {
        if (1 !== count($currentLine->formulaNumberDirection) || !LogicalExpression::isImplication($currentLine->ast)) {
            return null;
        }

        $ref = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        if (!$ref || !LogicalExpression::isImplication($ref->ast)) {
            return null;
        }

        $currentParts = static::getImplicationParts($currentLine->ast);
        $refParts = static::getImplicationParts($ref->ast);

        // For Absorption, check if the current implication is of the form A → (A ∧ B)
        $expectedConsequent = new AndExpression($refParts['antecedent'], $refParts['consequent']);

        return (
            LogicalExpressionComparator::areEqual($currentParts['antecedent'], $refParts['antecedent'])
            && LogicalExpressionComparator::areEqual($currentParts['consequent'], $expectedConsequent)
        ) ? 'Abs' : null;
    }

    /**
     * Detects Negation Introduction (¬I) in a proof step.
     *
     * Expected structure: The current AST must be a negation.
     * The method collects implications from references and checks for a pair
     * with a matching antecedent and contradictory consequents such that the
     * negated proposition equals the common antecedent.
     *
     * @param Proof   $currentLine the current proof step
     * @param Proof[] $allProofs   an array of all Proof objects
     *
     * @return string|null returns '¬I' if Negation Introduction is detected; otherwise, null
     */
    public static function detectNegIntro(Proof $currentLine, array $allProofs): ?string
    {
        if (!LogicalExpression::isNegation($currentLine->ast)) {
            return null;
        }

        $negatedProp = $currentLine->ast->proposition;
        $implications = [];

        // Collect all implications from the referenced lines indicated in formulaNumberDirection.
        foreach ($currentLine->formulaNumberDirection as $lineNum) {
            $ref = static::findProofByLine($lineNum, $allProofs);
            if ($ref) {
                if (LogicalExpression::isAnd($ref->ast)) {
                    // Split AndExpression into two Implications
                    $left = $ref->ast->left;
                    $right = $ref->ast->right;
                    if (LogicalExpression::isImplication($left)) {
                        $implications[] = $left;
                    }
                    if (LogicalExpression::isImplication($right)) {
                        $implications[] = $right;
                    }
                } elseif (LogicalExpression::isImplication($ref->ast)) {
                    $implications[] = $ref->ast;
                }
            }
        }

        // Check if there are at least two implications to compare.
        if (count($implications) < 2) {
            return null;
        }

        // Check if both implications share the same antecedent, and if the consequents are contradictory,
        // and if the negated proposition equals the common antecedent.
        foreach ($implications as $i => $impl1) {
            foreach ($implications as $j => $impl2) {
                if ($i === $j) {
                    continue;
                }

                $parts1 = static::getImplicationParts($impl1);
                $parts2 = static::getImplicationParts($impl2);

                // Antecedent matching and result inconsistency
                if (
                    LogicalExpressionComparator::areEqual($parts1['antecedent'], $parts2['antecedent'])
                    && LogicalExpressionComparator::areEqual($parts1['consequent'], new Negation($parts2['consequent']))
                    && LogicalExpressionComparator::areEqual($negatedProp, $parts1['antecedent'])
                ) {
                    return '¬I';
                }
            }
        }

        return null;
    }

    /**
     * Detects Conditional Proof (CPA) in a proof step.
     *
     * Expected structure: The current AST must be an implication.
     * The method identifies a referenced assumption (marked as 'Assume') that matches the antecedent,
     * and a referenced proof that matches the consequent.
     *
     * @param Proof   $currentLine the current proof step
     * @param Proof[] $allProofs   an array of all Proof objects
     *
     * @return string|null returns 'CPA' if Conditional Proof is detected; otherwise, null
     */
    public static function detectCPA(Proof $currentLine, array $allProofs): ?string
    {
        if (!LogicalExpression::isImplication($currentLine->ast)) {
            return null;
        }

        $implParts = static::getImplicationParts($currentLine->ast);
        $assumeLine = null;
        $conclusionLine = null;

        // Finding the hypothesis and conclusion among the references
        foreach ($currentLine->formulaNumberDirection as $lineNum) {
            $ref = static::findProofByLine($lineNum, $allProofs);
            if ('Assume' === $ref->autoType && LogicalExpressionComparator::areEqual($ref->ast, $implParts['antecedent'])) {
                $assumeLine = $ref;
            } elseif (LogicalExpressionComparator::areEqual($ref->ast, $implParts['consequent'])) {
                $conclusionLine = $ref;
            }
        }

        return ($assumeLine && $conclusionLine) ? 'CPA' : null;
    }

    /**
     * Detects Reductio ad Absurdum (RAA) in a proof step.
     *
     * Expected structure: The current AST must be a negation.
     * The formulaNumberDirection should contain exactly two line numbers, one for the assumption and one for the contradiction.
     *
     * The method checks that the referenced assumption and contradiction fulfill the requirements for RAA.
     *
     * @param Proof   $currentLine the current proof step
     * @param Proof[] $allProofs   an array of all Proof objects
     *
     * @return string|null returns 'RAA' if Reductio ad Absurdum is detected; otherwise, null
     */
    public static function detectRAA(Proof $currentLine, array $allProofs): ?string
    {
        // Ensure the current step's AST is a negation.
        if (!LogicalExpression::isNegation($currentLine->ast)) {
            return null;
        }

        $negatedProp = $currentLine->ast->proposition;

        // Expect exactly two entries in formulaNumberDirection (assumption and contradiction).
        if (2 !== count($currentLine->formulaNumberDirection)) {
            return null;
        }

        foreach ($currentLine->formulaNumberDirection as $lineNum) {
            $ref = static::findProofByLine($lineNum, $allProofs);
            if ('Assume' === $ref->autoType) {
                $assumeLine = $ref;
            } else {
                $contradictionLine = $ref;
            }
        }
        // $assumeLine = static::findProofByLine($currentLine->formulaNumberDirection[0], $allProofs);
        // $contradictionLine = static::findProofByLine($currentLine->formulaNumberDirection[1], $allProofs);

        // Check if the contradictionLine's AST is a conjunction and if its parts are contradictory.
        if (LogicalExpression::isAnd($contradictionLine->ast)) {
            $contradictionParts = [
                $contradictionLine->ast->left,
                $contradictionLine->ast->right,
            ];

            $hasContradiction = (
                LogicalExpressionComparator::areEqual($contradictionParts[0], new Negation($contradictionParts[1]))
                || LogicalExpressionComparator::areEqual(new Negation($contradictionParts[0]), $contradictionParts[1])
            );

            if (
                $hasContradiction
                && LogicalExpressionComparator::areEqual($assumeLine->ast, $negatedProp)
            ) {
                return 'RAA';
            }
        }

        return null;
    }

    /**
     * Sorts an array of LogicalExpression objects.
     *
     * @param LogicalExpression[] $props the array of LogicalExpression objects to sort
     *
     * @return LogicalExpression[] the sorted array of LogicalExpression objects
     */
    private static function sortLogicalExpressions(array $props): array
    {
        usort($props, function (LogicalExpression $a, LogicalExpression $b) {
            return static::compareLogicalExpressions($a, $b);
        });

        return $props;
    }

    /**
     * Compares two LogicalExpression objects.
     *
     * The comparison is based on:
     * - Class type.
     * - For Variables: their names.
     * - For Negation: recursively comparing the inner  expressions.
     * - For compound  expressions (And, OrExpression,  Implication, Biconditional): recursively comparing the left and right parts.
     *
     * @param LogicalExpression $a the first proposition
     * @param LogicalExpression $b the second proposition
     *
     * @return int returns -1, 0, or 1 as $a is less than, equal to, or greater than $b
     */
    private static function compareLogicalExpressions(LogicalExpression $a, LogicalExpression $b): int
    {
        // Compare class types.
        $classCompare = get_class($a) <=> get_class($b);
        if (0 !== $classCompare) {
            return $classCompare;
        }

        // For Variables, compare names.
        if (LogicalExpression::isVariable($a)) {
            /*
             * @var Variable $b
             * @var Variable $a
             */
            return $a->getName() <=> $b->getName();
        }

        // For Negation, compare inner  expressions.
        if (LogicalExpression::isNegation($a)) {
            /*
             * @var Negation $a
             * @var Negation $b
             */
            return static::compareLogicalExpressions($a->proposition, $b->proposition);
        }

        // For compound operators: AndExpression, OrExpression,  Implication, Biconditional.
        if (LogicalExpression::isAnd($a)
            || LogicalExpression::isOr($a)
            || LogicalExpression::isImplication($a)
            || LogicalExpression::isBiconditional($a)) {
            /**
             * @var AndExpression|Or|Implication|Biconditional $a
             * @var AndExpression|Or|Implication|Biconditional $b
             */
            $leftCompare = static::compareLogicalExpressions($a->left, $b->left);
            if (0 !== $leftCompare) {
                return $leftCompare;
            }

            return static::compareLogicalExpressions($a->right, $b->right);
        }

        return 0;
    }

    /**
     * Finds and returns a Proof object with the given line number from an array of proofs.
     *
     * @param int     $line   the line number to search for
     * @param Proof[] $proofs an array of Proof objects
     *
     * @return Proof|null the matching Proof object, or null if not found
     */
    private static function findProofByLine(int $line, array $proofs): ?Proof
    {
        foreach ($proofs as $proof) {
            if ($proof->line === $line) {
                return $proof;
            }
        }

        return null;
    }
}
