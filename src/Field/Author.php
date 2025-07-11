<?php

namespace coderstape\Press\Field;

use coderstape\Press\Author as AuthorModel;

class Author extends FieldContract
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
        return ['author_id' => (self::getOrCreateAuthor(trim($fieldValue)))->id];
    }

    /**
     * Creates an entry in the DB for the given series.
     *
     * @param $author
     *
     * @return \coderstape\Press\Tag
     */
    private static function getOrCreateAuthor($author)
    {
        return AuthorModel::firstOrCreate(['name' => $author]);
    }
}