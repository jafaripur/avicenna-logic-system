<?php

namespace Avicenna\Exception;

use Exception;

class MissingNotOperandException extends Exception
{
    public function getName(): string
    {
        return 'Missing not operand exception';
    }
}
