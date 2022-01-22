<?php

namespace Brezgalov\DomainModel\ResultFormatters;

interface IResultFormatter
{
    /**
     * @param $model
     * @param $result
     * @return mixed
     */
    public function format($model, $result);
}

