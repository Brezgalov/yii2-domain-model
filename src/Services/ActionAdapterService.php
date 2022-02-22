<?php

namespace Brezgalov\DomainModel\Services;

use Brezgalov\DomainModel\Services\Traits\ServiceTrait;
use Brezgalov\DomainModel\Services\Behaviors\ActionAdapterMutexBehavior;
use yii\base\Action;
use yii\base\InvalidConfigException;

class ActionAdapterService extends Action implements IService, IServiceSetup
{
    use ServiceTrait;

    const EVENT_BEFORE_RUN = 'beforeRun';
    const EVENT_AFTER_RUN = 'afterRun';

    /**
     * @var array
     */
    public $behaviors = [
        ActionAdapterMutexBehavior::class,
    ];

    /**
     * ActionAdapterService constructor.
     * @param $id
     * @param $controller
     * @param array $config
     */
    public function __construct($id, $controller, $config = [])
    {
        parent::__construct($id, $controller, $config);

        $this->attachBehaviors($this->behaviors);
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function getInput()
    {
        return array_merge(
            \Yii::$app->request->getBodyParams(),
            \Yii::$app->request->getQueryParams()
        );
    }

    /**
     * @return mixed
     * @throws InvalidConfigException
     */
    public function run()
    {
        $this->trigger(self::EVENT_BEFORE_RUN);

        $result = $this->handleAction();

        $this->trigger(self::EVENT_AFTER_RUN);

        return $result;
    }
}