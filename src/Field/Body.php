<?php

namespace coderstape\Press\Field;

use coderstape\Press\MarkdownParser;

class Body extends FieldContract
{
    /**
     * Process the field and make any needed modifications.
     *
     * @param $fieldType
     * @param $fieldValue
     * @param $fields
     *
     * @return array
     */
    public static function process($fieldType, $fieldValue, $fields)
    {
        return [
            'body' => MarkdownParser::parse($fieldValue),
        ];
    }
}