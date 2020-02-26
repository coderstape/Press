<?php

namespace coderstape\Press\Field;

use Carbon\Carbon;
use Exception;

class Date extends FieldContract
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
        try {

            $publishedAt = Carbon::createFromFormat('M d Y', $fieldValue)->startOfDay();

            return [
                'published_at' => $publishedAt,
                'active' => ! $publishedAt->isFuture(),
            ];

        }
        catch (Exception $e) {

            return ['published_at' => Carbon::now()->startOfDay()];

        }
    }
}
