<?php

namespace Avicenna\Exception;

use Exception;

class UnsupportedLogicTypeException extends Exception
{
    public function getName(): string
    {
        return 'Unsupported logic type exception';
    }
}
