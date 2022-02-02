<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\Exceptions\ErrorException;
use yii\base\Model;

abstract class BaseDomainActionModel extends Model implements IDomainActionModel
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

        if (!$this->validate()) {
            ErrorException::throw($this->getErrors(), 422);
        }
    }

    /**
     * @return mixed
     */
    public abstract function run();
}