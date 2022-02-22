<?php

namespace Brezgalov\DomainModel\Services;

use yii\base\InvalidCallException;
use Brezgalov\DomainModel\IDomainModel;
use Brezgalov\DomainModel\IDomainModelRepository;
use Brezgalov\DomainModel\ResultFormatters\IResultFormatter;
use Brezgalov\DomainModel\IUnitOfWork;
use yii\base\InvalidConfigException;

interface IService
{
    /**
     * @return \Exception|false|mixed
     */
    public function handleAction();
}