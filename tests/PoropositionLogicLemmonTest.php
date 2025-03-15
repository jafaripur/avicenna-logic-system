<?php

use Avicenna\Argument\ArgumentsParser;
use Avicenna\Exception\LemmonParserException;
use Avicenna\Method\Lemmon;
use Avicenna\Parsers\LogicParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

// #[CoversMethod(ArgumentsParser::class, 'create')]
#[CoversClass(Lemmon::class)]
final class PoropositionLogicLemmonTest extends TestCase
{
    private const DEDUCT_TRUE_1 = '
    [1]     (1)  p ∧ (q ∨ r)          [Premise]
    [1]     (2)  p                    [1, ∧E]
    [1]     (3)  q ∨ r                [1, ∧E]
    [4]     (4)  q                    [Assume]
    [1,4]   (5)  p ∧ q                [2, 4, ∧I]
    [1,4]   (6)  (p ∧ q) ∨ (p ∧ r)    [5, ∨I]
    [7]     (7)  r                    [Assume]
    [1,7]   (8)  p ∧ r                [2, 7, ∧I]
    [1,7]   (9)  (p ∧ q) ∨ (p ∧ r)    [8, ∨I]
    [1]     (10) (p∧q) ∨ (p ∧ r)      [3, 4, 6, 7, 9, ∨I]
    ';

    private const DEDUCT_FALSE_1 = '
    [1]     (1)  p ∧ (q ∨ r)          [Premise]
    [1]     (2)  p                    [1, ∧E]
    [1]     (3)  q ∨ r                [1, ∧E]
    [4]       q                    [Assume]
    [1,4]   (5)  p ∧ q                [2, 4, ∧I]
    [1,4]   (6)  (p ∧ q) ∨ (p ∧ r)
    [7]     (7)  r                    [Assume]
    [1,7]   (8)  p ∧ r                [2, 7, ∧I]
    [1,7]   (9)  (p ∧ q) ∨ (p ∧ r)    [8, ∨I]
    [1]     (10) (p∧q) ∨ (p ∧ r)      [3, 4, 6, 7, 9, ∨E]
    ';

    public function testArgumentParser(): void
    {
        $proofs = $this->parse(self::DEDUCT_TRUE_1);
        $this->assertCount(10, $proofs);
    }

    public function testArgumentParserError(): void
    {
        $this->expectException(LemmonParserException::class);
        $this->parse(self::DEDUCT_FALSE_1);
    }

    public function testArgumentRender(): void
    {
        $proofs = $this->parse(self::DEDUCT_TRUE_1);

        $this->assertNotEmpty(Lemmon::render($proofs));
    }

    /*
    public function testArgumentOutput(): void
    {
        $proofs = $this->parse(self::DEDUCT_TRUE_1);
        Lemmon::getOutput($proofs);
    }

    public function testArgumentOutputArgument(): void
    {
        $argument = 'P ∧ (Q ∨ R), P → ¬R ⊢ Q ∨ E';
        Lemmon::getOutputArgument(ArgumentsParser::create($argument));
    }*/

    private function parse(string $deduct): array
    {
        return Lemmon::parseInput($deduct, LogicParser::PROPOSITIONAL);
    }
}
