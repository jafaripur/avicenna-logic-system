<?php

namespace Avicenna\TruthTable;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class TruthTablePrinter.
 *
 * This class provides a static method to print the truth table analysis result in a tabular format
 * using a console output. It uses the Table class (with a ConsoleOutput) to render the table.
 */
final class TruthTablePrinter
{
    /**
     * Prints the truth table analysis result to the console.
     *
     * The table's headers are built by merging:
     *  - The atomic variable names (converted to uppercase).
     *  - The string representations of sub-expressions (from the expressionMap).
     *
     * For each truth assignment (row in the truth table), the method prints:
     *  - The truth values for each atomic variable ('T' for true, 'F' for false).
     *  - The evaluated results for each sub-expression (obtained from the proofs array using the key 'sub_' concatenated with the object's hash).
     *
     * After rendering the table, the method outputs whether the overall argument is valid.
     * If counterexamples exist (rows where all premises are true but the conclusion is false),
     * they are also printed.
     *
     * @param TruthTableAnalyzerResult $result the result object from truth table analysis
     */
    public static function print(TruthTableAnalyzerResult $result): void
    {
        $table = new Table(new ConsoleOutput());

        // Build headers: combine atomic variable names (uppercase) and sub-expression strings.
        $headers = array_merge(
            // array_map('strtoupper', $result->variables),
            $result->variables,
            array_values($result->expressionMap)
        );

        $table->setHeaders($headers);

        $values = [];

        // Iterate over each row of the truth table.
        foreach ($result->combinations as $i => $row) {
            $valueRow = [];
            // For each atomic variable, output 'T' if true and 'F' if false.
            foreach ($result->variables as $var) {
                $valueRow[] = $row[$var] ? 'T' : 'F';
            }
            // For each sub-expression, retrieve its evaluation result from the proofs map.
            foreach ($result->subExpressions as $expr) {
                $key = 'sub_'.spl_object_hash($expr);
                $valueRow[] = $result->proofs[$key]['results'][$i] ? 'T' : 'F';
            }

            $values[] = $valueRow;
        }

        $table->setRows($values);

        $table->render();

        // Output the overall validity of the argument.
        echo "\nArgument is ".($result->valid ? 'VALID' : 'INVALID');
        if (!empty($result->counterExamples)) {
            echo "\ncounterExamples:\n";
            foreach ($result->counterExamples as $ce) {
                echo '  • '.implode(', ', array_map(
                    fn (string $k, bool $v) => "{$k}: ".($v ? 'T' : 'F'),
                    array_keys($ce),
                    $ce
                ))."\n";
            }
        }
    }
}
