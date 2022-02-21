<?php

namespace Brezgalov\DomainModel\DAO;

use yii\base\Component;
use yii\db\Connection;
use yii\db\Query;
use yii\helpers\ArrayHelper;

abstract class MysqlAccessHelper extends Component
{
    /**
     * @return string
     */
    public function getPrimaryKeyName()
    {
        return 'id';
    }

    /**
     * @return string
     */
    public abstract function getTable();

    /**
     * @return Query
     */
    public function query()
    {
        return (new Query())
            ->select('*')
            ->from($this->getTable());
    }

    /**
     * @param array $columns
     * @param Connection|null $db
     * @return array|false|mixed
     * @throws \Exception
     */
    public function insert(array $columns, Connection $db = null)
    {
        $db = $db ?: \Yii::$app->db;
        $res = $db->schema->insert($this->getTable(), $columns);

        return is_array($res) ? ArrayHelper::getValue($res, $this->getPrimaryKeyName()) : $res;
    }

    /**
     * @param $condition - можно передать id как есть, он превратится в ['id' => $condition]
     * @param array $columns
     * @param Connection|null $db
     * @return \yii\db\Command
     */
    public function update($condition, array $columns, Connection $db = null)
    {
        $db = $db ?: \Yii::$app->db;

        if (is_integer($condition) || is_string($condition)) {
            $condition = [$this->getPrimaryKeyName() => $condition];
        }

        return $db->createCommand()->update($this->getTable(), $columns, $condition);
    }
}