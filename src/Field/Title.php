<?php

namespace vicgonvt\LaraPress\Field;

class Title extends FieldContract
{
    public static function process($fieldType, $fieldValue, $fields)
    {
        return [
            'slug' => (isset($fields['slug'])) ? $fields['slug'] : str_slug($fieldValue),
            'title' => $fieldValue,
        ];
    }
}