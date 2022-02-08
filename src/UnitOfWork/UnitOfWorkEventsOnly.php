<?php

namespace Brezgalov\DomainModel\UnitOfWork;

use Brezgalov\DomainModel\Events\DelayedEventsStorage;
use Brezgalov\DomainModel\Events\IEvent;

/**
 * Class UnitOfWorkDummy
 *
 * Иногда, если ваше действие только отдает информацию, например
 * вы хотите отключить UnitOfWork в целях оптимизации,
 * но сохранить доступ к отложенным событиям
 * для этого можно использовать UnitOfWorkEventsOnly
 *
 * @package Brezgalov\DomainModel
 */
class UnitOfWorkEventsOnly extends UnitOfWorkDummy
{
    /**
     * @var DelayedEventsStorage
     */
    protected $eventsStore;

    /**
     * UnitOfWorkEventsOnly constructor.
     * @param DelayedEventsStorage|null $eventsStore
     */
    public function __construct(DelayedEventsStorage $eventsStore = null)
    {
        $this->eventsStore = $eventsStore ?: new DelayedEventsStorage();
    }

    /**
     * @param IEvent $event
     */
    public function delayEvent(IEvent $event)
    {
        $this->eventsStore->delayEvent($event);
    }

    /**
     * @param IEvent $event
     * @param int|string $key
     */
    public function delayEventByKey(IEvent $event, $key)
    {
        $this->eventsStore->delayEventByKey($event, $key);
    }

    public function die()
    {
        $this->eventsStore->clearEvents();
    }

    public function flush()
    {
        $this->eventsStore->fireEvents();
    }
}