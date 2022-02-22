<?php

namespace Brezgalov\DomainModel\Services;

use Brezgalov\DomainModel\Services\Traits\ServiceTrait;
use \yii\base\Model;

class BaseService extends Model implements IService, IServiceSetup
{
    use ServiceTrait;

    /**
     * @var array
     */
    public $input = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['input'], 'safe'],
        ];
    }

    /**
     * @return array
     */
    public function getInput()
    {
        return $this->input;
    }
}