<?php

namespace vicgonvt\LaraPress\Field;

class Permalink
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
        return ['slug' => str_slug($fieldValue)];
    }
}
