<?php

namespace Brezgalov\DomainModel\Events;

use yii\base\Model;

class DelayedEventsStorage extends Model
{
    /**
     * @var IEvent[]
     */
    protected $events = [];

    /**
     * @var IEvent[]
     */
    protected $eventsFired = [];

    /**
     * @return IEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Use this func to delay some code for later use
     *
     * @param IEvent $event
     * @return $this
     */
    public function delayEvent(IEvent $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Use this func to delay some code for later use
     *
     * @param IEvent $event
     * @param int|string $key
     * @return $this
     */
    public function delayEventByKey(IEvent $event, $key)
    {
        $this->events[$key] = $event;

        return $this;
    }

    /**
     * Use your delayed code
     *
     * @return bool
     */
    public function fireEvents()
    {
        foreach ($this->events as $key => $event) {
            $event->run();

            $this->eventsFired[] = $event;
            unset($this->events[$key]);
        }

        return true;
    }

    /**
     * Remove delayed events
     * @return bool
     */
    public function clearEvents()
    {
        $this->events = [];

        return true;
    }
}