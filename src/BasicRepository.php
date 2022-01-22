<?php

namespace Brezgalov\DomainModel;

use Brezgalov\DomainModel\Exceptions\ErrorException;
use yii\base\Model;

abstract class BasicRepository extends Model implements IDomainModelRepository
{
    /**
     * @param array $data
     * @return bool
     * @throws ErrorException
     */
    public function registerInput(array $data = [])
    {
        $this->load($data, '');
        
        if (!$this->validate()) {
            ErrorException::throwException($this->getErrors(), 422);
        }
        
        return true;
    }
}