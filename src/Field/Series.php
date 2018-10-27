<?php

namespace vicgonvt\LaraPress\Field;

use vicgonvt\LaraPress\Series as SeriesModel;

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
     * @return \vicgonvt\LaraPress\Tag
     */
    private static function getOrCreateSeries($series)
    {
        return SeriesModel::firstOrCreate(
            ['slug' => str_slug($series)],
            ['title' => $series]
        );
    }
}