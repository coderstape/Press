<?php

namespace vicgonvt\LaraPress\Field;

class Extra extends FieldContract
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
        if (isset($fields['extra'])) {
            $extra = json_decode($fields['extra']);

            $extra = array_merge((array)$extra, [$fieldType => $fieldValue]);
        } else {
            $extra = [$fieldType => $fieldValue];
        }

        return ['extra' => json_encode($extra)];
    }
}
