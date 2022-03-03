<?php

namespace Brezgalov\DomainModel\DAO;

use Brezgalov\DomainModel\IRegisterInputInterface;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveQuery;

abstract class BaseDaoRepository extends Model implements IDaoRepository, IRegisterInputInterface
{
    /**
     * @var callable[]
     */
    protected $queryCallbacks = [];

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
     * Можно добавить декоратор запроса, чтобы кастомизировать результирующий запрос
     * Формат fun(ActiveQuery $query, $tableAlias, BaseDaoRepository $repo)
     *
     * @param callable $callback
     * @return $this
     */
    public function addQueryDecorator(callable $callback)
    {
        $this->queryCallbacks[] = $callback;

        return $this;
    }

    /**
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public function getQuery()
    {
        $alias = $this->getQueryAlias();

        $query = $this->getBaseQuery($alias);

        foreach ($this->queryCallbacks as $callback) {
            if (is_callable($callback)) {
                $query = call_user_func($callback, $query, $alias, $this);
            }
        }

        return $query;
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