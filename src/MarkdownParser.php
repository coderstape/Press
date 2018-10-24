<?php

namespace vicgonvt\LaraPress;

use Parsedown;

class MarkdownParser
{
    /**
     * Given a markdown string, it will pass back a parsed string.
     *
     * @param $text
     *
     * @return string
     */
    public static function parse($text)
    {
        return Parsedown::instance()->text($text);
    }
}