<?php

namespace Brezgalov\DomainModel\Events;

interface IEvent
{
    /**
     * @return bool
     */
    public function run();
}