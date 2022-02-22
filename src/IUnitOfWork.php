<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\Events\IEvent;

interface IUnitOfWork
{
    /**
     * @return void
     */
    public function ready();

    /**
     * @param IDomainModel $model
     */
    public function die(IDomainModel $model);

    /**
     * @param IDomainModel $model
     * @return void
     */
    public function flush(IDomainModel $model);
}