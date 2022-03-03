<?php

namespace Brezgalov\DomainModel\DAO;

use yii\db\ActiveQuery;

interface IDaoRepository
{
    /**
     * @return ActiveQuery
     */
    public function getQuery();
}