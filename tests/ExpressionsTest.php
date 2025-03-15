<?php

use Avicenna\Proposition\Expressions\AndExpression;
use Avicenna\Proposition\Expressions\Biconditional;
use Avicenna\Proposition\Expressions\Implication;
use Avicenna\Proposition\Expressions\LogicalExpression;
use Avicenna\Proposition\Expressions\Negation;
use Avicenna\Proposition\Expressions\OrExpression;
use Avicenna\Proposition\Expressions\Variable;
use Avicenna\Proposition\Expressions\XorExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogicalExpression::class)]
final class ExpressionsTest extends TestCase
{
    public function testVariable(): void
    {
        $var = new Variable('P');
        $context = ['P' => true];
        $this->assertTrue($var->evaluate($context));
        // Additional test case
        $context = ['P' => false];
        $this->assertFalse($var->evaluate($context));
    }

    public function testNegation(): void
    {
        $var = new Variable('P');
        $neg = new Negation($var);
        $context = ['P' => true];
        $this->assertFalse($neg->evaluate($context));
        // Additional test case
        $context = ['P' => false];
        $this->assertTrue($neg->evaluate($context));
    }

    public function testAndExpression(): void
    {
        $left = new Variable('P');
        $right = new Variable('Q');
        $and = new AndExpression($left, $right);
        $context = ['P' => true, 'Q' => true];
        $this->assertTrue($and->evaluate($context));
        // Additional test cases
        $context = ['P' => true, 'Q' => false];
        $this->assertFalse($and->evaluate($context));
        $context = ['P' => false, 'Q' => true];
        $this->assertFalse($and->evaluate($context));
        $context = ['P' => false, 'Q' => false];
        $this->assertFalse($and->evaluate($context));
    }

    public function testOrExpression(): void
    {
        $left = new Variable('P');
        $right = new Variable('Q');
        $or = new OrExpression($left, $right);
        $context = ['P' => false, 'Q' => true];
        $this->assertTrue($or->evaluate($context));
        // Additional test cases
        $context = ['P' => true, 'Q' => false];
        $this->assertTrue($or->evaluate($context));
        $context = ['P' => true, 'Q' => true];
        $this->assertTrue($or->evaluate($context));
        $context = ['P' => false, 'Q' => false];
        $this->assertFalse($or->evaluate($context));
    }

    public function testXorExpression(): void
    {
        $left = new Variable('P');
        $right = new Variable('Q');
        $xor = new XorExpression($left, $right);
        $context = ['P' => true, 'Q' => false];
        $this->assertTrue($xor->evaluate($context));
        // Additional test cases
        $context = ['P' => false, 'Q' => true];
        $this->assertTrue($xor->evaluate($context));
        $context = ['P' => true, 'Q' => true];
        $this->assertFalse($xor->evaluate($context));
        $context = ['P' => false, 'Q' => false];
        $this->assertFalse($xor->evaluate($context));
    }

    public function testImplication(): void
    {
        $left = new Variable('P');
        $right = new Variable('Q');
        $imp = new Implication($left, $right);
        $context = ['P' => true, 'Q' => true];
        $this->assertTrue($imp->evaluate($context));
        // Additional test cases
        $context = ['P' => true, 'Q' => false];
        $this->assertFalse($imp->evaluate($context));
        $context = ['P' => false, 'Q' => true];
        $this->assertTrue($imp->evaluate($context));
        $context = ['P' => false, 'Q' => false];
        $this->assertTrue($imp->evaluate($context));
    }

    public function testBiconditional(): void
    {
        $left = new Variable('P');
        $right = new Variable('Q');
        $bicond = new Biconditional($left, $right);
        $context = ['P' => true, 'Q' => true];
        $this->assertTrue($bicond->evaluate($context));
        // Additional test cases
        $context = ['P' => true, 'Q' => false];
        $this->assertFalse($bicond->evaluate($context));
        $context = ['P' => false, 'Q' => true];
        $this->assertFalse($bicond->evaluate($context));
        $context = ['P' => false, 'Q' => false];
        $this->assertTrue($bicond->evaluate($context));
    }
}
