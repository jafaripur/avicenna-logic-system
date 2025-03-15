<?php

namespace Avicenna\Method;

use Avicenna\Argument\ArgumentsParserResult;
use Avicenna\Exception\LemmonParserException;
use Avicenna\Proposition\Proof;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Final class Lemmon.
 *
 * This class implements the SyntaxMethod interface for processing proofs in Lemmon style.
 * It provides methods to parse input, render proofs as SVG, and output formatted tables.
 */
final class Lemmon extends SyntaxMethod
{
    /**
     * Parses the input string in Lemmon style and returns an array of Proof objects.
     *
     * Expected input format:
     *   [<ref_list>] (<line_number>) <formula> [<user_rule> with <rule>]
     *
     * - <ref_list>: A comma-separated list of reference line numbers.
     * - <line_number>: The line number of the proof step (inside parentheses).
     * - <formula>: The proposition or argument formula.
     * - <user_rule>: (Optional) The rule provided by the user along with any reference numbers.
     *
     * @param string $input     the input string containing the proof steps
     * @param int    $logicType The type of logic to use (e.g., LogicParser::PROPOSITIONAL).
     *
     * @return Proof[] an array of Proof objects parsed from the input
     *
     * @throws LemmonParserException if any line does not match the expected format
     */
    public static function parseInput(string $input, int $logicType): array
    {
        /**
         * @var Proof[] $proofs
         */
        $proofs = [];

        // Split the input string by newlines.
        $lines = explode("\n", trim($input));

        // Regular expression pattern to extract references, line number, formula, and details.
        $pattern = '/^\[(?<references>.*?)\]\s*\((?<line>\d+)\)\s*(?<formula>.+?)\s*\[(?<details>.*?)\]$/';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Check if the line matches the expected format.
            if (!preg_match($pattern, $line, $matches)) {
                throw new LemmonParserException("Invalid line format: {$line}");
            }

            // Process the references: split by comma, trim, and convert to integers.
            $references = array_map('intval',
                array_filter(
                    explode(',', $matches['references']),
                    fn ($x) => is_numeric(trim($x))
                )
            );

            // Process the details part to extract any numeric values and the rule text.
            $detailParts = preg_split('/(?<=\d)(,)(?=\D)|(?<=\D)(,)(?=\d)/', $matches['details'], -1, PREG_SPLIT_DELIM_CAPTURE);
            $numbers = [];
            $text = '';

            foreach ($detailParts as $part) {
                $part = trim($part);
                if (',' === $part) {
                    continue;
                }

                if (is_numeric($part)) {
                    $numbers[] = (int) $part;
                } else {
                    $text = $part;

                    break;
                }
            }

            $lineNumber = (int) $matches['line'];

            // Determine autoType: if the only reference is the current line number,
            // then classify as 'Premise' or 'Assume' based on the extracted text.
            $autoType = null;
            if (1 === count($references) && $references[0] === $lineNumber) {
                // If a user rule is provided, we keep it; otherwise, mark as 'Premise/Assume'
                if (!empty($text)) {
                    $autoType = 'Premise' == $text ? 'Premise' : 'Assume';
                }
            }

            // Create a new Proof object with the parsed data.
            $proofs[] = new Proof(
                $lineNumber, strtoupper(trim($matches['formula'])), $references,
                $text, null, $autoType,
                $numbers, $logicType
            );
        }

        // For each proof step, detect the inference rules based on all proofs.
        foreach ($proofs as $step) {
            $step->detectRules($proofs);
        }

        return $proofs;
    }

    /**
     * Renders an array of Proof objects into an SVG representation.
     *
     * Each proof line is rendered following the format:
     *   [<ref_list>] (<line_number>) <formula> [<user_rule> with <rule>]
     *
     * The method calculates the maximum width for each column and aligns columns accordingly.
     *
     * @param Proof[] $proofs an array of Proof objects
     *
     * @return string the SVG output as a string
     */
    public static function render(array $proofs): string
    {
        // Prepare an array of rows where each row is an array of 4 columns:
        // Column 1: Reference list (e.g., "1,2")
        // Column 2: Line number (e.g., "(3)")
        // Column 3: Formula (e.g., "P ∧ Q")
        // Column 4: User rule part (e.g., "2, 4, MPP" or "Premise")
        $rows = [];
        foreach ($proofs as $proof) {
            $refList = !empty($proof->references) ? implode(',', $proof->references) : '';
            $lineNumber = '('.$proof->line.')';
            $formula = $proof->formula;
            $fnDir = $proof->formulaNumberDirection;
            if (!empty($fnDir)) {
                $userRule = implode(',', $fnDir);
                if (null !== $proof->detectedRule && '' !== $proof->detectedRule) {
                    $userRule .= ', '.$proof->detectedRule;
                }
            } else {
                $userRule = $proof->detectedRule ?? $proof->rule ?? '';
            }
            $rows[] = [$refList, $lineNumber, $formula, $userRule];
        }

        // Determine maximum width (in pixels) for each column using mb_strwidth for better accuracy.
        // Assume a monospace font where average character width is approximately 8 pixels.
        $charWidth = 8;
        $padding = 20; // extra padding per column
        $numColumns = 4;
        $maxWidths = array_fill(0, $numColumns, 0);

        foreach ($rows as $cols) {
            for ($i = 0; $i < $numColumns; ++$i) {
                // Calculate width as (number of characters * charWidth)
                $width = mb_strwidth($cols[$i]) * $charWidth;
                if ($width > $maxWidths[$i]) {
                    $maxWidths[$i] = $width;
                }
            }
        }

        // Calculate x positions for each column cumulatively.
        $xPositions = [];
        $currentX = $padding;
        for ($i = 0; $i < $numColumns; ++$i) {
            $xPositions[$i] = $currentX;
            $currentX += $maxWidths[$i] + $padding;
        }

        $svgWidth = $currentX;
        $lineHeight = 24;
        $svgHeight = count($rows) * $lineHeight + 2 * $padding;

        // Start building SVG output.
        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="{$svgWidth}" height="{$svgHeight}">
            <style>
                .proofLine { font: 14px monospace; }
            </style>
            SVG;

        $y = $padding + $lineHeight;
        foreach ($rows as $cols) {
            // Wrap column 1 and column 4 in brackets if not empty.
            $col1 = '' !== $cols[0] ? sprintf('[%s]', htmlspecialchars($cols[0])) : '';
            $col2 = htmlspecialchars($cols[1]);
            $col3 = htmlspecialchars($cols[2]);
            $col4 = '' !== $cols[3] ? sprintf('[%s]', htmlspecialchars($cols[3])) : '';

            $svg .= "\n  <text class=\"proofLine\" x=\"{$xPositions[0]}\" y=\"{$y}\">{$col1}</text>";
            $svg .= "\n  <text class=\"proofLine\" x=\"{$xPositions[1]}\" y=\"{$y}\">{$col2}</text>";
            $svg .= "\n  <text class=\"proofLine\" x=\"{$xPositions[2]}\" y=\"{$y}\">{$col3}</text>";
            $svg .= "\n  <text class=\"proofLine\" x=\"{$xPositions[3]}\" y=\"{$y}\">{$col4}</text>";
            $y += $lineHeight;
        }

        $svg .= "\n</svg>";

        return $svg;
    }

    /**
     * Outputs a formatted version of the parsed proofs as a table.
     *
     * This method formats each proof line in the format:
     *   [<ref_list>] (<line_number>) <formula> [<user_rule> with <rule>]
     *
     * The output is printed to the console.
     *
     * @param Proof[] $proofs an array of Proof objects
     */
    public static function getOutput(array $proofs): void
    {
        $rows = [];

        foreach ($proofs as $proof) {
            $rule = $proof->autoType;

            if (empty($rule)) {
                $rule = $proof->detectedRule;
            }

            $rows[] = [
                '['.(implode(',', $proof->references)).']',
                '('.$proof->line.')',
                $proof->formula,
                '['.($proof->formulaNumberDirection ? ($rule.', '.implode(', ', $proof->formulaNumberDirection)) : $rule).']',
            ];
        }

        static::printTable('Parsed argument', $rows);
    }

    /**
     * Outputs a formatted version of the argument by splitting it into proofs.
     *
     * For each premise, a formatted row is generated.
     *
     * @param ArgumentsParserResult $proofs the result object containing parsed premises
     */
    public static function getOutputArgument(ArgumentsParserResult $proofs): void
    {
        $rows = [];

        foreach ($proofs->getPremises() as $key => $premise) {
            $rows[] = [
                "[{$premise->line}]",
                "({$premise->line})",
                $premise->formula,
                '['.(empty($premise->formulaNumberDirection) ? 'Assume' : 'Premise').']',
            ];
        }

        static::printTable('Arguements split to proofs', $rows);
    }

    /**
     * Prints a table to the console.
     *
     * This method uses a ConsoleOutput and a Table class to render the rows.
     * It sets a header title and column widths.
     *
     * @param string $title the title for the table
     * @param array  $rows  the table rows
     * @param string $style the style to apply (default is 'compact')
     */
    private static function printTable(string $title, array $rows, string $style = 'compact'): void
    {
        $table = new Table(new ConsoleOutput());
        $table->setStyle($style);
        $table->setHeaderTitle($title);

        // $table->setHeaders(['Refrence', 'Line', 'Formula', 'Rule']);
        $table->setHeaders(['', '', '', '']);

        $table->setColumnWidths([1, 1, 20, 0]);

        $table->setRows($rows);

        $table->render();
    }
}
