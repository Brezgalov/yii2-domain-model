<?php

namespace Brezgalov\DomainModel\DTO;

use Brezgalov\DomainModel\IDomainModel;
use yii\base\Model;

class CrossDomainCallDto extends Model
{
    /**
     * @var IDomainModel
     */
    public $model;

    /**
     * @var mixed
     */
    public $result;
}