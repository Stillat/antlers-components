<?php

namespace Stillat\AntlersComponents\Utilities;

class StringUtilities
{
    public static function normalizeLineEndings($string, $to = "\n")
    {
        return preg_replace("/\r\n|\r|\n/", $to, $string);
    }
}
