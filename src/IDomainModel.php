<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\DTO\CrossDomainCallDto;
use Brezgalov\DomainModel\Events\IEvent;
use yii\base\InvalidConfigException;

interface IDomainModel
{
    /**
     * @return array
     */
    public function actions();

    /**
     * list of actions allowed for cross-domain access
     *
     * @return array
     */
    public function crossDomainActionsAllowed();

    /**
     * @param array $data
     * @return void
     */
    public function registerInput(array $data = []);

    /**
     * @param $originDMClass
     * @return bool
     */
    public function registerCrossDomainOrigin(string $originDMClass);

    /**
     * @return array
     */
    public function getCrossDomainOrigin();

    /**
     * Вызывает экшен модели
     *
     * @param $actionName
     * @param array $input
     * @return false|mixed|void
     */
    public function call($actionName, array $input = []);

    /**
     * @return array
     */
    public function getInput();

    /**
     * Проверка состояния модели
     * @return bool
     */
    public function isValid();

    /**
     * @param IUnitOfWork $unitOfWork
     */
    public function linkUnitOfWork(IUnitOfWork $unitOfWork);

    /**
     * @return IUnitOfWork
     */
    public function getUnitOfWork();

    /**
     * allows to delay events inside DomainActionModel
     *
     * @param IEvent $event
     */
    public function delayEvent(IEvent $event);

    /**
     * allows to delay events inside DomainActionModel
     *
     * @param IEvent $event
     */
    public function delayEventByKey(IEvent $event, $key);

    /**
     * hen you need to create a cross-domain call
     * you should call DomainModel instead of DomainModelAction
     * calling Model with this method allows single UnitOfWork
     * between models
     *
     * @param array|string|IDomainModel|IDomainModelRepository $modelConfig
     * @param string $methodName
     * @param array $params
     * @return CrossDomainCallDto
     * @throws InvalidConfigException
     */
    public function crossDomainCall($modelConfig, string $methodName, array $params = []);

    /**
     * @return bool
     */
    public function canInitWithoutRepo();
}