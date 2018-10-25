<?php

namespace vicgonvt\LaraPress\Field;

class Identifier
{
    public static function process($fieldType, $fieldValue, $fields)
    {
        return ['identifier' => str_slug($fieldValue)];
    }
}
