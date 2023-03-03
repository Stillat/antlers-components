<?php

namespace Stillat\AntlersComponents\Utilities;

use Illuminate\Support\Fluent;

class StringableFluent extends Fluent
{
    public function __toString(): string
    {
        return $this->attributes['{string}'];
    }
}
