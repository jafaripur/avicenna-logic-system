<?php

use Avicenna\Parsers\LogicParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogicParser::class)]
final class PoropositionLogicParserTest extends TestCase
{
    public static function argumentsProvider(): array
    {
        return [
            [
                '(P ∧ ¬(Q → (R ↔ S))) → T',
                true, // result
            ],
        ];
    }

    #[DataProvider('argumentsProvider')]
    public function testParserAndEvaluate(string $argument, bool $result): void
    {
        $proposition = LogicParser::parseFormula($argument, LogicParser::PROPOSITIONAL);

        $context = [
            'P' => true,
            'Q' => false,
            'R' => true,
            'S' => false,
            'T' => true,
        ];

        $this->assertEquals($proposition->evaluate($context), $result);
    }
}
