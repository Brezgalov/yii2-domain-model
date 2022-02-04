<?php

namespace Brezgalov\DomainModel\Services;

use yii\base\InvalidCallException;
use Brezgalov\DomainModel\Exceptions\ErrorException;
use Brezgalov\DomainModel\IDomainModel;
use Brezgalov\DomainModel\IDomainModelRepository;
use Brezgalov\DomainModel\ResultFormatters\IResultFormatter;
use Brezgalov\DomainModel\Services\Behaviors\ActionAdapterMutexBehavior;
use Brezgalov\DomainModel\IUnitOfWork;
use Brezgalov\DomainModel\UnitOfWork;
use yii\base\Action;

class ActionAdapterService extends Action
{
    const EVENT_BEFORE_RUN = 'beforeRun';
    const EVENT_AFTER_RUN = 'afterRun';

    /**
     * @var array
     */
    public $behaviors = [
        ActionAdapterMutexBehavior::class,
    ];

    /**
     * @var string|array|IDomainModelRepository
     */
    public $repository;

    /**
     * @var string|array|IDomainModel
     */
    public $model;

    /**
     * @var string
     */
    public $actionName;

    /**
     * @var string|array|IUnitOfWork
     */
    public $unitOfWork = UnitOfWork::class;

    /**
     * @var string|array|IResultFormatter
     */
    public $formatter;

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
     * @throws \yii\base\InvalidConfigException
     */
    protected function getInput()
    {
        return array_merge(
            \Yii::$app->request->getBodyParams(),
            \Yii::$app->request->getQueryParams()
        );
    }

    /**
     * @return IDomainModelRepository
     * @throws \yii\base\InvalidConfigException
     */
    public function getDomainModelRepository()
    {
        if ($this->repository instanceof IDomainModelRepository) {
            return $this->repository;
        }

        return \Yii::createObject($this->repository);
    }

    /**
     * @return IDomainModel
     * @throws \yii\base\InvalidConfigException
     */
    public function getDomainModel()
    {
        $input = $this->getInput();

        if ($this->model) {
            $model = $this->model instanceof IDomainModel ? $this->model : \Yii::createObject($this->model);
            $model->registerInput($input);

            return $model;
        }

        $repo = $this->getDomainModelRepository();
        $repo->registerInput($input);

        return $repo->getDomainModel();
    }

    /**
     * @return IUnitOfWork
     * @throws \yii\base\InvalidConfigException
     */
    public function getUnitOfWork()
    {
        if ($this->unitOfWork instanceof IUnitOfWork) {
            return $this->unitOfWork;
        }

        return \Yii::createObject($this->unitOfWork);
    }

    /**
     * @return IResultFormatter|object
     * @throws \yii\base\InvalidConfigException
     */
    public function getFormatter()
    {
        if ($this->formatter instanceof IResultFormatter) {
            return $this->formatter;
        }

        return $this->formatter ? \Yii::createObject($this->formatter) : null;
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $this->trigger(self::EVENT_BEFORE_RUN);

        $unitOfWork = null;
        $model = null;
        $resultFormatter = null;

        try {
            $resultFormatter = $this->getFormatter();
            $model = $this->getDomainModel();

            $unitOfWork = $this->getUnitOfWork();
            $model->linkUnitOfWork($unitOfWork);

            $result = $model->call($this->actionName);
            if (!$model->isValid()) {
                throw new InvalidCallException('Action lead to invalid state');
            }

            if ($result === false) {
                $model->getUnitOfWork()->die();
            } else {
                $model->getUnitOfWork()->flush();
            }
        } catch (\Exception $ex) {
            $result = $ex;

            if ($unitOfWork) {
                $model->getUnitOfWork()->die();
            }
        }

        $this->trigger(self::EVENT_AFTER_RUN);

        return $resultFormatter ? $resultFormatter->format($model, $result) : $result;
    }
}