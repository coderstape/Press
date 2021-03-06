<?php

namespace coderstape\Press\Transformers;

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