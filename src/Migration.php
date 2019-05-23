<?php

namespace coderstape\Press;

abstract class Migration extends \Illuminate\Database\Migrations\Migration
{
    /**
     * @var \Illuminate\Config\Repository|mixed
     */
    protected $prefix;

    /**
     * Migration constructor.
     */
    public function __construct()
    {
        $this->prefix = config('press.prefix');
    }
}
