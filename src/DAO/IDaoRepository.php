<?php

namespace Brezgalov\DomainModel\DAO;

use yii\db\ActiveQuery;

interface IDaoRepository
{
    /**
     * @return ActiveQuery
     */
    public function getQuery();

    /**
     * @param array $data
     * @return bool
     */
    public function registerInput(array $data = []);
}