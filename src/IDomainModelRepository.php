<?php

namespace Brezgalov\DomainModel;

interface IDomainModelRepository
{
    /**
     * @param array $data
     * @return bool
     */
    public function registerInput(array $data = []);

    /**
     * @return IDomainModel
     * @throws \yii\base\InvalidConfigException
     */
    public function getDomainModel();
}