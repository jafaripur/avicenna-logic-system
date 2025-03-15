<?php

namespace Avicenna\Exception;

use Exception;

class MalformedExpressionException extends Exception
{
    public function getName(): string
    {
        return 'Malformed expression exception';
    }
}
