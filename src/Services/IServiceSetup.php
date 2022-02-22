<?php

namespace Brezgalov\DomainModel\Services;

use Brezgalov\DomainModel\IDomainModel;
use Brezgalov\DomainModel\IDomainModelRepository;
use Brezgalov\DomainModel\IUnitOfWork;
use Brezgalov\DomainModel\ResultFormatters\IResultFormatter;

interface IServiceSetup
{
    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function getInput();

    /**
     * @return IDomainModelRepository
     * @throws InvalidConfigException
     */
    public function getDomainModelRepository();

    /**
     * @return IDomainModel
     * @throws InvalidConfigException
     */
    public function getDomainModel();

    /**
     * @return IUnitOfWork
     * @throws InvalidConfigException
     */
    public function getUnitOfWork();

    /**
     * @return IResultFormatter|object
     * @throws InvalidConfigException
     */
    public function getFormatter();

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getActionName();

}