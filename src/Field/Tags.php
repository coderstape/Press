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

        return ['tag_ids' => array_pluck($tagModels, 'id')];
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
        return Tag::firstOrCreate([
            'slug' => str_slug($tag),
            'name' => $tag,
        ]);
    }
}