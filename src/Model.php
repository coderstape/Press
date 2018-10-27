<?php

namespace vicgonvt\LaraPress;

use Illuminate\Support\Str;

class Model extends \Illuminate\Database\Eloquent\Model
{
    protected $prefix;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->prefix = config('larapress.prefix', 'larepress_');
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (! isset($this->table)) {
            return $this->prefix . str_replace(
                '\\', '', Str::snake(Str::plural(class_basename($this)))
            );
        }

        return $this->table;
    }
}