<?php

namespace vicgonvt\LaraPress\Field;

abstract class FieldContract
{
    public static function process($fieldType, $fieldValue)
    {
        return [$fieldType => $fieldValue];
    }
}
