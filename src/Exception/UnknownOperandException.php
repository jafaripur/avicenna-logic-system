<?php

namespace Avicenna\Exception;

use Exception;

class UnknownOperandException extends Exception
{
    public function getName(): string
    {
        return 'Unknown operand exception';
    }
}
