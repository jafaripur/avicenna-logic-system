<?php

namespace Avicenna\Exception;

use Exception;

class InsufficientOperandException extends Exception
{
    public function getName(): string
    {
        return 'Insufficient operand exception';
    }
}
