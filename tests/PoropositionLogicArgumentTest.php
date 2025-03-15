<?php

use Avicenna\Argument\ArgumentsParser;
use Avicenna\Exception\ArgumentConclusionException;
use Avicenna\Proposition\Proof;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(ArgumentsParser::class, 'create')]
// #[CoversClass(ArgumentsParser::class)]
final class PoropositionLogicArgumentTest extends TestCase
{
    public function testArgumentParser(): void
    {
        $argument = 'P ∧ (Q ∨ R), P → ¬R ⊢ Q ∨ E';

        $result = ArgumentsParser::create($argument);

        $this->assertCount(2, $result->getPremises());
        $this->assertInstanceOf(Proof::class, $result->getConclusion());
    }

    public function testArgumentParserTwoConclusion(): void
    {
        $argument = 'P ∧ (Q ∨ R), P → ¬R ⊢ Q ∨ E ⊢ A';

        $this->expectException(ArgumentConclusionException::class);

        ArgumentsParser::create($argument);
    }

    public function testArgumentParserNoConclusion(): void
    {
        $argument = 'P ∧ (Q ∨ R), P → ¬R';

        $this->expectException(ArgumentConclusionException::class);

        ArgumentsParser::create($argument);
    }
}
