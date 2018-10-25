<?php

namespace vicgonvt\LaraPress\Field;

use vicgonvt\LaraPress\Series as SeriesModel;

class Series extends FieldContract
{
    public static function handle($fieldType, $fieldValue)
    {
        $series = array_map(function ($field) {
            return trim($field);
        }, explode(',', $fieldValue));

        foreach ($series as $series) {
            if (self::isNewSeries($series)) {
                self::addSeries($series);
            }
        }
    }

    /**
     * Creates an entry in the DB for the given series.
     *
     * @param $series
     */
    private static function addSeries($series)
    {
        SeriesModel::create([
            'slug' => str_slug($series),
            'title' => $series,
        ]);
    }

    /**
     * Checks if the series exists in the DB.
     *
     * @param $series
     *
     * @return bool
     */
    private static function isNewSeries($series)
    {
        return ! SeriesModel::where('slug', str_slug($series))->exists();
    }
}