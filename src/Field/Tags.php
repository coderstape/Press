<?php

namespace coderstape\Press\Field;

use coderstape\Press\Tag;

class Tags extends FieldContract
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
        $tags = array_map(function ($tag) {
            return trim($tag);
        }, explode(',', $fieldValue));

        foreach ($tags as $tag) {
            $tagModels[] = self::getOrCreateTag($tag);
        }

        return ['tag_ids' => \Arr::pluck($tagModels, 'id')];
    }

    /**
     * Creates an entry in the DB for the given tag.
     *
     * @param $tag
     *
     * @return \coderstape\Press\Tag
     */
    private static function getOrCreateTag($tag)
    {
        // Match on slug ONLY (it's unique in the schema). Matching on
        // name too meant a case-variant tag ('Laravel' after 'laravel')
        // missed the lookup and crashed ingest on the duplicate-slug
        // insert. First spelling wins the display name -- same rule as
        // Field\Series. Pinned in FieldsTest.
        return Tag::firstOrCreate(
            ['slug' => \Str::slug($tag)],
            ['name' => $tag]
        );
    }
}
