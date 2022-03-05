<?php

namespace Brezgalov\DomainModel\Services\Traits;

use Brezgalov\DomainModel\Events\AfterFlushEvent;
use Brezgalov\DomainModel\IRegisterInputInterface;
use yii\base\InvalidCallException;
use Brezgalov\DomainModel\IDomainModel;
use Brezgalov\DomainModel\IDomainModelRepository;
use Brezgalov\DomainModel\ResultFormatters\IResultFormatter;
use Brezgalov\DomainModel\IUnitOfWork;
use Brezgalov\DomainModel\UnitOfWork;
use yii\base\InvalidConfigException;

trait ServiceTrait
{
    /**
     * @var string
     */
    public $actionName;

    /**
     * @var string|array|IDomainModelRepository
     */
    public $repository;

    /**
     * @var string|array|IDomainModel
     */
    public $model;

    /**
     * @var string|array|IUnitOfWork
     */
    public $unitOfWork = UnitOfWork::class;

    /**
     * @var string|array|IResultFormatter
     */
    public $formatter;

    /**
     * @var AfterFlushEvent
     */
    public $afterFlushEvent;

    /**
     * @var bool
     */
    public $emptyModelAllowed = false;

    /**
     * @return IDomainModelRepository
     * @throws InvalidConfigException
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
     * @throws InvalidConfigException
     */
    public function getDomainModel()
    {
        $input = $this->getInput();

        if ($this->model) {
            $model = $this->model instanceof IDomainModel ? $this->model : \Yii::createObject($this->model);

            if (!$model->canInitWithoutRepo()) {
                throw new InvalidCallException('Model ' . get_class($model) . ' can not be loaded without Repo');
            }

            if ($model instanceof IRegisterInputInterface) {
                $model->registerInput($input);
            }

            return $model;
        }

        $repo = $this->getDomainModelRepository();

        if ($repo instanceof IRegisterInputInterface) {
            $repo->registerInput($input);
        }

        return $repo->getDomainModel();
    }

    /**
     * @return IUnitOfWork
     * @throws InvalidConfigException
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
     * @throws InvalidConfigException
     */
    public function getFormatter()
    {
        if ($this->formatter instanceof IResultFormatter) {
            return $this->formatter;
        }

        return $this->formatter ? \Yii::createObject($this->formatter) : null;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getActionName()
    {
        if (empty($this->actionName)) {
            throw new InvalidConfigException('actionName should be set');
        }

        return $this->actionName;
    }

    /**
     * @param IDomainModel|null $model
     */
    protected function callAfterFlushEvent(IDomainModel $model = null)
    {
        $afterFlushEvent = $this->afterFlushEvent;
        if ($afterFlushEvent && (is_string($afterFlushEvent) || is_array($afterFlushEvent))) {
            $afterFlushEvent = \Yii::createObject($afterFlushEvent);
        }

        if ($afterFlushEvent instanceof AfterFlushEvent) {
            if ($model) {
                $afterFlushEvent->setModel($model);
            }

            $afterFlushEvent
                ->setInput($this->getInput())
                ->run();
        }
    }

    /**
     * @return \Exception|false|mixed|void
     */
    public function handleAction()
    {
        $unitOfWork = null;
        $model = null;

        try {
            $model = $this->getDomainModel();

            if (empty($model)) {
                if ($this->emptyModelAllowed) {
                    $this->callAfterFlushEvent();

                    return $this->formatResult(null, null);
                }

                throw new InvalidConfigException("Model not instantiated");
            }

            if (!$model->isValid()) {
                throw new InvalidConfigException("Model " . get_class($model) . " loaded in failed state");
            }

            $unitOfWork = $this->getUnitOfWork();
            $unitOfWork->ready();

            $result = $model->call($this->getActionName());
            if (!$model->isValid()) {
                throw new InvalidCallException('Action lead to invalid state');
            }

            if ($result === false) {
                $unitOfWork->die($model);
            } else {
                $unitOfWork->flush($model);

                $this->callAfterFlushEvent($model);
            }
        } catch (\Exception $ex) {
            $result = $ex;

            if ($unitOfWork) {
                $unitOfWork->die($model);
            }
        }

        return $this->formatResult($model, $result);
    }

    /**
     * @param IDomainModel $model
     * @param mixed $result
     * @return mixed
     */
    public function formatResult($model, $result)
    {
        $resultFormatter = $this->getFormatter();

        return $resultFormatter ? $resultFormatter->format($model, $result) : $result;
    }
}