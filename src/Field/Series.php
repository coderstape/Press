<?php

namespace coderstape\Press\Field;

use coderstape\Press\Series as SeriesModel;

class Series extends FieldContract
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
        return ['series_id' => (self::getOrCreateSeries(trim($fieldValue)))->id];
    }

    /**
     * Creates an entry in the DB for the given series.
     *
     * @param $series
     *
     * @return \coderstape\Press\Tag
     */
    private static function getOrCreateSeries($series)
    {
        return SeriesModel::firstOrCreate(
            ['slug' => \Str::slug($series)],
            ['title' => $series]
        );
    }
}