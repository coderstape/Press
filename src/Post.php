<?php

namespace vicgonvt\LaraPress;

class Post extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be typecasted as an instance of Carbon.
     *
     * @var array
     */
    protected $dates = ['published_at'];

    /**
     * Get the fully qualified path to this post.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function path()
    {
        return url(LaraPress::path() . "/posts/{$this->id}-{$this->slug}");
    }

    /**
     * Parse the 'extra' column and return the appropriate field.
     *
     * @param $field
     *
     * @return mixed
     */
    public function extra($field)
    {
        return optional(json_decode($this->extra))->$field;
    }

    /**
     * Returns the image path from the extras field.
     *
     * @return mixed
     */
    public function image()
    {
        return $this->extra('img');
    }

    /**
     * Scope the posts to only those set to active.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Get the series that this post belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get the tags that this post is tagged with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, $this->prefix . 'post_tag');
    }
}