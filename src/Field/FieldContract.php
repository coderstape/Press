<?php

namespace vicgonvt\LaraPress\Field;

abstract class FieldContract
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
        return [$fieldType => $fieldValue];
    }

    public static function handle($fieldType, $fieldValue) {}
}
