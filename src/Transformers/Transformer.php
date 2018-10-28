<?php

namespace vicgonvt\LaraPress\Transformers;

interface Transformer
{
    /**
     * Transform the model for the necessary use.
     *
     * @param $model
     *
     * @return array
     */
    public function transform($model);
}