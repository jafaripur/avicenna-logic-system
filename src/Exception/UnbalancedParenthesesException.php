<?php

namespace Avicenna\Exception;

use Exception;

class UnbalancedParenthesesException extends Exception
{
    public function getName(): string
    {
        return 'Unbalanced parentheses exception';
    }
}
