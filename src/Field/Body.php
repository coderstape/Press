<?php

namespace vicgonvt\LaraPress\Field;

use vicgonvt\LaraPress\MarkdownParser;

class Body extends FieldContract
{
    public static function process($fieldType, $fieldValue, $fields)
    {
        return [
            'body' => MarkdownParser::parse($fieldValue),
        ];
    }
}