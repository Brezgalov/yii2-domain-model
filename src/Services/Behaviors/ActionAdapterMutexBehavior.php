<?php

namespace Brezgalov\DomainModel\Services\Behaviors;

use Brezgalov\DomainModel\Services\ActionAdapterService;
use yii\base\Behavior;
use yii\mutex\Mutex;
use yii\web\Request;
use yii\web\ServerErrorHttpException;

class ActionAdapterMutexBehavior extends Behavior
{
    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var bool
     */
    public $mutexNameWithIp = true;

    /**
     * @var Mutex
     */
    public $mutexComp;

    /**
     * seconds before mutex exception
     * @var int
     */
    public $mutexTimeout = 30;

    /**
     * ActionAdapterMutexBehavior constructor.
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (empty($this->mutexComp) && \Yii::$app->has('mutex')) {
            $this->mutexComp = \Yii::$app->get('mutex');
        }

        $controller = \Yii::$app->controller;

        if (empty($this->controller) && $controller) {
            $this->controller = $controller->id;
        }

        if (empty($this->action) && $controller && $controller->action) {
            $this->action = $controller->action->id;
        }
    }

    /**
     * @return string|null
     */
    public function getControllerId()
    {
        return $this->controller;
    }

    /**
     * @return string|null
     */
    public function getActionId()
    {
        return $this->action;
    }

    /**
     * @return mixed|string|null
     * @throws \yii\base\InvalidConfigException
     */
    public function getUserIp()
    {
        if ($this->ip) {
            return $this->ip;
        }

        if (\Yii::$app->has('request')) {
            /** @var Request $request */
            $request = \Yii::$app->get('request');

            if ($request) {
                $this->ip = $request->getUserIP();
            }
        }

        return $this->ip;
    }

    /**
     * @return string
     */
    protected function buildActionMutexName()
    {
        $mutexName = $this->getControllerId() . '/' . $this->getActionId();

        if ($this->mutexNameWithIp) {
            $ip = $this->getUserIp();
            if ($ip) {
                $mutexName .= '/' . $this->ip;
            }
        }

        return $mutexName;
    }

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActionAdapterService::EVENT_BEFORE_RUN => 'acquireMutex',
            ActionAdapterService::EVENT_AFTER_RUN => 'releaseMutex',
        ];
    }

    /**
     * @return bool
     * @throws ServerErrorHttpException
     */
    public function acquireMutex()
    {
        if (empty($this->mutexComp)) {
            return true;
        }

        $mutexName = $this->buildActionMutexName();

        if (!$this->mutexComp->acquire($mutexName, $this->mutexTimeout)) {
            throw new ServerErrorHttpException("Lock can not be acquired after {$this->mutexTimeout} seconds");
        }

        return true;
    }

    /**
     * @return bool
     */
    public function releaseMutex()
    {
        if (empty($this->mutexComp)) {
            return true;
        }

        $mutexName = $this->buildActionMutexName();

        if (!$this->mutexComp->isAcquired($mutexName)) {
            return true;
        }

        return $this->mutexComp->release($mutexName);
    }
}