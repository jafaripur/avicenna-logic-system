<?php

namespace Avicenna\Exception;

use Exception;

class ArgumentConclusionException extends Exception
{
    public function getName(): string
    {
        return 'Argument conclusion exception';
    }
}
