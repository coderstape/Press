<?php

namespace coderstape\Press\Field;

class Identifier extends FieldContract
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
        return ['identifier' => \Str::slug($fieldValue)];
    }
}
