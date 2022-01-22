<?php

namespace Brezgalov\DomainModel;

use yii\base\Model;

abstract class BasicDomainActionModel extends Model implements IDomainActionModel
{
    /**
     * @var IDomainModel
     */
    protected $model;

    /**
     * BasicDomainActionModel constructor.
     * @param IDomainModel $model
     * @param array $config
     */
    public function __construct(IDomainModel $model, $config = [])
    {
        $this->model = $model;

        parent::__construct($config);
    }

    /**
     * Pass input to model
     *
     * @param array $data
     * @return bool|void
     */
    public function registerInput(array $data = [])
    {
        parent::load($data, '');
    }

    /**
     * @return mixed
     */
    public abstract function run();
}