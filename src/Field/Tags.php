<?php

namespace vicgonvt\LaraPress\Field;

use vicgonvt\LaraPress\Tag;

class Tags extends FieldContract
{
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
     * @return \vicgonvt\LaraPress\Tag
     */
    private static function getOrCreateTag($tag)
    {
        return Tag::firstOrCreate([
            'slug' => str_slug($tag),
            'name' => $tag,
        ]);
    }
}