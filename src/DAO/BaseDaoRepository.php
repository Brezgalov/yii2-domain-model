<?php

namespace Brezgalov\DomainModel\DAO;

use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveQuery;

abstract class BaseDaoRepository extends Model implements IDaoRepository
{
    /**
     * ActiveRecord class
     * @var string
     */
    public $daoClass;

    /**
     * alias for base table
     * @var string
     */
    public $queryAlias;

    /**
     * @return false|mixed|string
     * @throws InvalidConfigException
     */
    protected function getQueryAlias()
    {
        if ($this->queryAlias) {
            return $this->queryAlias;
        }

        if (empty($this->daoClass)) {
            throw new InvalidConfigException(static::class . ' repo requires dao class to be set');
        }

        return forward_static_call([$this->daoClass, 'tableName']);
    }

    /**
     * @param $alias
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    protected function getBaseQuery($alias)
    {
        if (empty($this->daoClass)) {
            throw new InvalidConfigException(static::class . ' repo requires dao class to be set');
        }

        $query = forward_static_call([$this->daoClass, 'find']);

        if (!($query instanceof ActiveQuery)) {
            throw new InvalidCallException('find result supposed to be ' . ActiveQuery::class);
        }

        $query->alias($alias);
        
        return $query;
    }

    /**
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public function getQuery()
    {
        return $this->getBaseQuery(
            $this->getQueryAlias()
        );
    }

    /**
     * @param array $data
     * @return bool
     */
    public function registerInput(array $data = [])
    {
        return $this->load($data, '');
    }
}