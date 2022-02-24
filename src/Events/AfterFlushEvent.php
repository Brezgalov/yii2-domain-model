<?php

namespace Brezgalov\DomainModel\Events;

use Brezgalov\DomainModel\IDomainModel;
use yii\base\Component;

abstract class AfterFlushEvent extends Component implements IEvent
{
    /**
     * @var IDomainModel
     */
    protected $model;

    /**
     * @var array
     */
    protected $input;

    /**
     * @param IDomainModel $model
     * @return $this
     */
    public function setModel(IDomainModel $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param array $input
     * @return $this
     */
    public function setInput(array $input)
    {
        $this->input = $input;

        return $this;
    }

    public abstract function run();
}