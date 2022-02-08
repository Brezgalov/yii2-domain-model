<?php

namespace Brezgalov\DomainModel\UnitOfWork;

use Brezgalov\DomainModel\Events\DelayedEventsStorage;
use Brezgalov\DomainModel\Events\IEvent;
use Brezgalov\DomainModel\IUnitOfWork;
use yii\base\Model;
use yii\db\Connection;
use yii\db\Transaction;

/**
 * Class UnitOfWorkDummy
 *
 * Иногда, если ваше действие только отдает информацию, например
 * вы хотите отключить UnitOfWork в целях оптимизации, для этого можно использовать Dummy
 *
 * @package Brezgalov\DomainModel
 */
class UnitOfWorkDummy implements IUnitOfWork
{
    /**
     * @param IEvent $event
     */
    public function delayEvent(IEvent $event)
    {
        // dummy
    }

    /**
     * @param IEvent $event
     * @param int|string $key
     */
    public function delayEventByKey(IEvent $event, $key)
    {
        // dummy
    }

    public function ready()
    {
        // dummy
    }

    public function die()
    {
        // dummy
    }

    public function flush()
    {
        // dummy
    }
}