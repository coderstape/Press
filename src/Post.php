<?php

namespace coderstape\Press;

use coderstape\Press\Facades\Press;

class Post extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    protected $appends = ['author', 'contentable'];

    /**
     * The attributes that should be typecasted as an instance of Carbon.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the fully qualified path to this post.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function path()
    {
        return url(Press::path() . "/{$this->id}-{$this->slug}");
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
        return $this->extra('img') ?: config('press.blog.image');
    }

    /**
     * Record a visit for this post.
     */
    public function recordVisit()
    {
        $this->visits()->create();
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
        if (Press::isEditor()) {
            return $query;
        }

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
     * Get the author that this post belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(Author::class);
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

    /**
     * Gets all the visits for this post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function visits()
    {
        return $this->hasMany(Trending::class, 'post_id');
    }

    /**
     * If using the database driver, this will fetch the raw blog from the blogs table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function blog()
    {
        return $this->belongsTo(Blog::class, 'identifier');
    }

    /**
     * Returns the morphed relationship of follow up.
     */
    public function contentable(): MorphOne
    {
        return $this->morphOne(\App\Models\AIContent::class, 'contentable');
    }
}
