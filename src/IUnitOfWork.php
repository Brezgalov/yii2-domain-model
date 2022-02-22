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
     * @return void
     */
    public function die();

    /**
     * @param IDomainModel $model
     * @return void
     */
    public function flush(IDomainModel $model);
}