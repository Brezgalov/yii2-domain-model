<?php

namespace Brezgalov\DomainModel\Services;

use Brezgalov\DomainModel\IDomainModel;
use Brezgalov\DomainModel\IDomainModelRepository;
use Brezgalov\DomainModel\ResultFormatters\ModelResultFormatter;
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
    public $modelActionName;

    /**
     * @var string|array|IUnitOfWork
     */
    public $unitOfWork = UnitOfWork::class;

    /**
     * @var string|array|IResultFormatter
     */
    public $resultFormatter = ModelResultFormatter::class;

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
        if ($this->model) {
            if ($this->model instanceof IDomainModel) {
                return $this->model;
            }

            return \Yii::createObject($this->model);
        }

        $repo = $this->getDomainModelRepository();
        $repo->registerInput($this->getInput());

        return $repo->loadDomainModel();
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
    public function getResultFormatter()
    {
        if ($this->resultFormatter instanceof IResultFormatter) {
            return $this->resultFormatter;
        }

        return $this->resultFormatter ? \Yii::createObject($this->resultFormatter) : null;
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $this->trigger(self::EVENT_BEFORE_RUN);

        $model = $this->getDomainModel();
        $resultFormatter = $this->getResultFormatter();

        $unitOfWork = $this->getUnitOfWork();
        $model->linkUnitOfWork($unitOfWork);

        try {
            $result = call_user_func([$model, $this->modelActionName]);
            $model->getUnitOfWork()->flush();
        } catch (\Exception $ex) {
            $result = $ex;
            $model->getUnitOfWork()->die();
        }

        $this->trigger(self::EVENT_AFTER_RUN);

        return $resultFormatter ? $resultFormatter->format($model, $result) : $result;
    }
}