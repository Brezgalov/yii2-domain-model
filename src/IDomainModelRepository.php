<?php

namespace Brezgalov\DomainModel;

interface IDomainModelRepository
{
    /**
     * @return IDomainModel
     * @throws \yii\base\InvalidConfigException
     */
    public function getDomainModel();
}