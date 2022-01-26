<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\DTO\CrossDomainCallDto;
use Brezgalov\DomainModel\Events\DelayedEventsStorage;
use Brezgalov\DomainModel\Events\IEvent;
use Brezgalov\DomainModel\Exceptions\CrossDomainException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Class BasicDomainModel
 * Provides DelayedEventStorage wrapping
 * @package Brezgalov\DomainModel
 */
abstract class BaseDomainModel extends Model implements IDomainModel
{
    /**
     * @var array
     */
    protected $crossDomainOrigin = [];

    /**
     * @var IUnitOfWork
     */
    protected $unitOfWork;

    /**
     * @var array
     */
    protected $input = [];

    /**
     * @return array
     */
    public function actions()
    {
        return [];
    }

    /**
     * list of actions allowed for cross-domain access
     *
     * @return array
     */
    public function crossDomainActionsAllowed()
    {
        return [];
    }

    /**
     * @param string $originDMClass
     * @return bool
     */
    public function registerCrossDomainOrigin(string $originDMClass)
    {
        $this->crossDomainOrigin[] = $originDMClass;
    }

    /**
     * @return array
     */
    public function getCrossDomainOrigin()
    {
        return $this->crossDomainOrigin;
    }

    /**
     * @param string $name
     * @param array $params
     * @return false|mixed
     * @throws InvalidConfigException
     */
    public function __call($name, $params)
    {
        $params = array_merge($this->input, $params);

        $action = ArrayHelper::getValue($this->actions(), $name);

        if (is_callable($action)) {
            return call_user_func($action, ...$params);
        }

        if (is_string($action) || is_array($action)) {
            $action = \Yii::createObject($action, ['model' => $this]);
        }

        if ($action instanceof IDomainActionModel) {
            $action->registerInput($params);
            return $action->run();
        }

        return parent::__call($name, $params);
    }

    /**
     * Pass input to model
     *
     * @param array $data
     * @return void
     */
    public function registerInput(array $data = [])
    {
        $this->input = $data;
    }

    /**
     * @return array
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Link unitOfWork to delay storage-write and events
     *
     * @param IUnitOfWork $unitOfWork
     */
    public function linkUnitOfWork(IUnitOfWork $unitOfWork)
    {
        $this->unitOfWork = &$unitOfWork;
        $this->unitOfWork->ready();
    }

    /**
     * @return IUnitOfWork
     */
    public function getUnitOfWork()
    {
        return clone $this->unitOfWork;
    }

    /**
     * allows to delay events inside DomainActionModel
     *
     * @param IEvent $event
     * @throws InvalidConfigException
     */
    public function delayEvent(IEvent $event)
    {
        if (!$this->unitOfWork) {
            throw new InvalidConfigException('UnitOfWork not defined in domain model ' . static::class);
        }

        $this->unitOfWork->delayEvent($event);
    }

    /**
     * Use this func to delay some code for later use
     *
     * @param IEvent $event
     * @param int|string $key
     * @throws InvalidConfigException
     */
    public function delayEventByKey(IEvent $event, $key)
    {
        if (!$this->unitOfWork) {
            throw new InvalidConfigException('UnitOfWork not defined in domain model ' . static::class);
        }

        $this->unitOfWork->delayEventByKey($event, $key);
    }

    /**
     * when you need to create a cross-domain call
     * you should call DomainModel instead of DomainModelAction
     * calling Model with this method allows single UnitOfWork
     * between models
     *
     * @param array|string|IDomainModel|IDomainModelRepository $modelConfig
     * @param string $methodName
     * @param array $input
     * @return CrossDomainCallDto
     * @throws InvalidConfigException
     */
    public function crossDomainCall($modelConfig, string $methodName, array $input = [])
    {
        $model = null;

        if (is_array($modelConfig) || is_string($modelConfig)) {
            $modelConfig = \Yii::createObject($modelConfig);
        }

        if ($modelConfig instanceof IDomainModelRepository) {
            $modelConfig->registerInput($input);
            $modelConfig = $modelConfig->getDomainModel();
        }

        if (!($modelConfig instanceof IDomainModel)) {
            CrossDomainException::throwException(static::class, null, "Only Models and Repos can be accessed in cross-domain way");
        }

        $modelConfig->registerCrossDomainOrigin(static::class);

        if (!in_array($methodName, $modelConfig->crossDomainActionsAllowed())) {
            CrossDomainException::throwException(static::class, get_class($modelConfig), "Method {$methodName} is not allowed for cross-domain access");
        }

        /**
         * pass UnitOfWork by ref, so events storage and transaction stays "singltoned"
         */
        if ($this->unitOfWork) {
            $modelConfig->linkUnitOfWork($this->unitOfWork);
        }

        $modelConfig->registerInput($input);

        $result = call_user_func([$modelConfig, $methodName]);

        return new CrossDomainCallDto([
            'model' => $modelConfig,
            'result' => $result,
        ]);
    }
}