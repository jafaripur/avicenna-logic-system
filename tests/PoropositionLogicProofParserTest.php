<?php

use Avicenna\Method\Lemmon;
use Avicenna\Parsers\LogicParser;
use Avicenna\Proposition\Proof;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing()]
final class PoropositionLogicProofParserTest extends TestCase
{
    public static function deductsProvider(): array
    {
        return [
            [
                '
                [1]     (1)  (p∧ (q∨ r))          [Premise]
                [1]     (2)  p                    [1, ∧E]
                [1]     (3)  q ∨ r                [1, ∧E]
                [4]     (4)  q                    [Assume]
                [1,4]   (5)  p ∧ q                [2, 4, ∧I]
                [1,4]   (6)  (p ∧ q) ∨ (p ∧ r)    [5, ∨I]
                [7]     (7)  r                    [Assume]
                [1,7]   (8)  p ∧ r                [2, 7, ∧I]
                [1,7]   (9)  (p ∧ q) ∨ (p ∧ r)    [8, ∨I]
                [1]     (10) (p∧q) ∨ (p ∧ r)      [3, 4, 6, 7, 9, disjelim]
                ',
                8, // Combinations count
                ['P', 'Q', 'R'], // Variable
                ['Contingent', 'Contingent', 'Contingent', 'Contingent', 'Contingent', 'Contingent', 'Contingent', 'Contingent', 'Contingent', 'Contingent'], // classification for each line
                [true, true, true, true, true, true, true, true, true, true], // valid rule for each line
                true, // Valid argument or not
            ],
        ];
    }

    #[DataProvider('deductsProvider')]
    public function testProofsParser(string $deduct, int $combinationCount, array $variableList, array $classification, array $proofLineValid, bool $valid): void
    {
        $proofs = Lemmon::parseInput($deduct, LogicParser::PROPOSITIONAL);

        foreach ($proofs as $proof) {
            if (empty($proof->autoType)) {
                $this->assertEquals($proof->checkUserRuleIsValid(), true);
            }
        }
    }

    public function testProofFormatSpacing(): void
    {
        $this->assertEquals(Proof::formatSpacing('(P∧ (Q∨ R))'), 'P ∧ (Q ∨ R)');
    }

    #[DataProvider('deductsProvider')]
    public function testProofAnalyze(string $deduct, int $combinationCount, array $variableList, array $classification, array $proofLineValid, bool $valid): void
    {
        $proofs = Lemmon::parseInput($deduct, LogicParser::PROPOSITIONAL);

        $result = Proof::analyze($proofs);
        $this->assertCount($combinationCount, $result->combinations);
        $this->assertEquals($variableList, $result->variables);

        $this->assertEquals($result->valid, true);

        if (!$result->valid) {
            $this->assertGreaterThan(1, count($result->counterExamples));
        }

        foreach ($result->proofs as $key => $proof) {
            if (is_numeric($key)) {
                $this->assertEquals($proof['classification'], $classification[$key - 1]);
            }
        }
    }

    #[DataProvider('deductsProvider')]
    public function testProofLineValidRule(string $deduct, int $combinationCount, array $variableList, array $classification, array $proofLineValid, bool $valid): void
    {
        $proofs = Lemmon::parseInput($deduct, LogicParser::PROPOSITIONAL);

        foreach ($proofs as $key => $proof) {
            $this->assertEquals($proof->checkUserRuleIsValid(), $proofLineValid[$key]);
        }
    }
}
