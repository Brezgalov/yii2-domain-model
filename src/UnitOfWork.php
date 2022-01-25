<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\Events\DelayedEventsStorage;
use Brezgalov\DomainModel\Events\IEvent;
use yii\base\Model;
use yii\db\Connection;
use yii\db\Transaction;

/**
 * Class UnitOfWork
 * Handle delayed (write or "do smth") operations such as events and transactions
 * @package Brezgalov\DomainModel
 */
class UnitOfWork extends Model implements IUnitOfWork
{
    /**
     * @var Connection
     */
    public $dbComponent;

    /**
     * @var Transaction
     */
    protected $trans;

    /**
     * @var DelayedEventsStorage
     */
    protected $eventsStore;

    /**
     * UnitOfWork constructor.
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (empty($this->dbComponent) && \Yii::$app->has('db')) {
            $this->dbComponent = \Yii::$app->get('db');
        }

        if (empty($this->eventsStore)) {
            $this->eventsStore = new DelayedEventsStorage();
        }
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

    public function ready()
    {
        if (!$this->trans) {
            $this->trans = $this->dbComponent->beginTransaction();
        }
    }

    public function die()
    {
        $this->trans->rollBack();
    }

    public function flush()
    {
        $this->trans->commit();

        $this->eventsStore->fireEvents();
    }
}