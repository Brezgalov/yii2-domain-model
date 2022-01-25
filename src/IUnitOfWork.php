<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\Events\IEvent;

interface IUnitOfWork
{
    /**
     * @param IEvent $event
     * @return void
     */
    public function delayEvent(IEvent $event);

    /**
     * @param IEvent $event
     * @param int|string $key
     * @return void
     */
    public function delayEventByKey(IEvent $event, $key);

    /**
     * @return void
     */
    public function ready();

    /**
     * @return void
     */
    public function die();

    /**
     * @return void
     */
    public function flush();
}