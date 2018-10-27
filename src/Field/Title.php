<?php

namespace vicgonvt\LaraPress\Field;

class Title extends FieldContract
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
        return [
            'slug' => (isset($fields['slug'])) ? $fields['slug'] : str_slug($fieldValue),
            'title' => $fieldValue,
        ];
    }
}