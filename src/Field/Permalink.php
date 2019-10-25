<?php

namespace coderstape\Press\Field;

class Permalink extends FieldContract
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
        return ['slug' => \Str::slug($fieldValue)];
    }
}
