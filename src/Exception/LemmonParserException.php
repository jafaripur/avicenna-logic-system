<?php

namespace Avicenna\Exception;

use Exception;

class LemmonParserException extends Exception
{
    public function getName(): string
    {
        return 'Lemmon parser exception';
    }
}
