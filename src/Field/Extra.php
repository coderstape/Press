<?php

namespace vicgonvt\LaraPress\Field;

class Extra
{
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
