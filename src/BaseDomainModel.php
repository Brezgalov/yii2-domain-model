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
abstract class BaseDomainModel extends Model implements IDomainModel, IRegisterInputInterface
{
    /**
     * @var bool
     */
    protected $noRepoAllowed = false;

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
     * @var DelayedEventsStorage
     */
    protected $eventsStore;

    /**
     * BaseDomainModel constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (empty($this->eventsStore)) {
            $this->eventsStore = new DelayedEventsStorage();
        }
    }

    /**
     * @param DelayedEventsStorage $storage
     */
    public function linkEventsStore(DelayedEventsStorage $storage)
    {
        $this->eventsStore = &$storage;
    }

    /**
     * @return bool
     */
    public function clearEvents()
    {
        return $this->eventsStore->clearEvents();
    }

    /**
     * @return bool
     */
    public function fireEvents()
    {
        return $this->eventsStore->fireEvents();
    }

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
     * Вызывает экшен модели
     *
     * @param $actionName
     * @param array $input
     * @return false|mixed|void
     */
    public function call($actionName, array $input = [])
    {
        $action = ArrayHelper::getValue($this->actions(), $actionName);

        if (is_callable($action)) {
            return call_user_func($action, $input);
        }

        if (is_string($action) || is_array($action)) {
            $action = \Yii::createObject($action, ['model' => $this]);
        }

        if ($action instanceof IDomainActionModel) {
            if ($action instanceof IRegisterInputInterface) {
                $action->registerInput(array_merge($this->input, $input));
            }

            return $action->run();
        }

        throw new InvalidCallException('Action not found');
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
     * Проверка состояния модели
     * @return bool
     */
    public function isValid()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * allows to delay events inside DomainActionModel
     *
     * @param IEvent $event
     * @throws InvalidConfigException
     */
    public function delayEvent(IEvent $event)
    {
        if (!$this->eventsStore) {
            throw new InvalidConfigException('eventsStore not defined in domain model ' . static::class);
        }

        $this->eventsStore->delayEvent($event);
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
        if (!$this->eventsStore) {
            throw new InvalidConfigException('eventsStore not defined in domain model ' . static::class);
        }

        $this->eventsStore->delayEventByKey($event, $key);
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

        // Если модель передана напрямую - она должна иметь соответствующее разрешение
        if (
            $modelConfig instanceof IDomainModel &&
            !$modelConfig->canInitWithoutRepo()
        ) {
            CrossDomainException::throwException(static::class, get_class($modelConfig), "Model can not be called straight. Use Repo");
        }

        if ($modelConfig instanceof IDomainModelRepository) {
            /**
             * Если репозиторий передан на прямую - кросс-доменный вызов не должна вносить в него артефакты
             * Если нет - проще сделать лишний clone, чем плодить if'ы
             */
            $modelConfig = clone $modelConfig;

            if ($modelConfig instanceof IRegisterInputInterface) {
                $modelConfig->registerInput($input);
            }

            $modelConfig = $modelConfig->getDomainModel();
        }

        if (!($modelConfig instanceof IDomainModel)) {
            CrossDomainException::throwException(static::class, null, "Only Models and Repos can be accessed in cross-domain way");
        }

        if (!$modelConfig->isValid()) {
            CrossDomainException::throwException(static::class, get_class($modelConfig), "Model loaded in invalid state");
        }

        /**
         * Если модель передана на прямую - кросс-доменный вызов не должна вносить в нее артефакты
         * Если нет - проще сделать лишний clone, чем плодить if'ы
         */
        $modelConfig = clone $modelConfig;
        $modelConfig->registerCrossDomainOrigin(static::class);
        $modelConfig->linkEventsStore($this->eventsStore);

        if (!in_array($methodName, $modelConfig->crossDomainActionsAllowed())) {
            CrossDomainException::throwException(static::class, get_class($modelConfig), "Method {$methodName} is not allowed for cross-domain access");
        }

        $result = $modelConfig->call($methodName, $input);

        return new CrossDomainCallDto([
            'model' => $modelConfig,
            'result' => $result,
        ]);
    }

    /**
     * Очень редко нужно обратиться к своим методам
     * в модели которая уже получена, через репозиторий.
     * Используем вот такой "хак"
     *
     * @return IDomainModel
     */
    public function getNoRepoClone()
    {
        $clone = clone $this;
        $clone->noRepoAllowed = true;

        return $clone;
    }

    /**
     * @return bool
     */
    public function canInitWithoutRepo()
    {
        return $this->noRepoAllowed;
    }
}