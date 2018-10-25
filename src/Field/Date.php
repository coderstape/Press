<?php

namespace vicgonvt\LaraPress\Field;

use Carbon\Carbon;
use Exception;

class Date extends FieldContract
{
    public static function process($fieldType, $fieldValue, $fields)
    {
        try {

            return [
                'published_at' => Carbon::createFromFormat('M d Y', $fieldValue)->startOfDay()
            ];

        }
        catch (Exception $e) {

            return ['published_at' => Carbon::now()->startOfDay()];

        }
    }
}