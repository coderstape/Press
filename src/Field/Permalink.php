<?php

namespace vicgonvt\LaraPress\Field;

class Permalink
{
    public static function process($fieldType, $fieldValue, $fields)
    {
        return ['slug' => str_slug($fieldValue)];
    }
}
