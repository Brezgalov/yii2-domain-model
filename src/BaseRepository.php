<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\Exceptions\ErrorException;
use yii\base\Model;
use yii\base\InvalidConfigException;

abstract class BaseRepository extends Model implements IDomainModelRepository, IRegisterInputInterface
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @param array $data
     * @return bool
     * @throws ErrorException
     */
    public function registerInput(array $data = [])
    {
        $this->input = $data;

        $this->load($data, '');

        if (!$this->validate()) {
            ErrorException::throw($this->getErrors(), 422);
        }

        return true;
    }

    /**
     * @return IDomainModel
     */
    public abstract function loadDomainModel();

    /**
     * @return IDomainModel
     * @throws \yii\base\InvalidConfigException
     */
    public function getDomainModel()
    {
        $model = $this->loadDomainModel();

        if ($model instanceof IRegisterInputInterface) {
            $model->registerInput($this->input);
        }

        return $model;
    }
}