<?php

namespace vicgonvt\LaraPress\Field;

use vicgonvt\LaraPress\Tag;

class Tags extends FieldContract
{
    public static function handle($fieldType, $fieldValue)
    {
        $tags = array_map(function ($tag) {
            return trim($tag);
        }, explode(',', $fieldValue));

        foreach ($tags as $tag) {
            if (self::isNewTag($tag)) {
                self::addTag($tag);
            }
        }
    }

    /**
     * Creates an entry in the DB for the given tag.
     *
     * @param $tag
     */
    private static function addtag($tag)
    {
        Tag::create([
            'slug' => str_slug($tag),
            'name' => $tag,
        ]);
    }

    /**
     * Checks if the tag exists in the DB.
     *
     * @param $tag
     *
     * @return bool
     */
    private static function isNewTag($tag)
    {
        return ! Tag::where('slug', str_slug($tag))->exists();
    }
}