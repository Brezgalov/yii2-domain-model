<?php

namespace app\domain\UserProfile\Events;

use app\domain\UserProfile\UserProfileDM;
use Brezgalov\DomainModel\Events\IEvent;
use yii\base\Model;
use yii\db\Exception;

class StoreModelEvent extends Model implements IEvent
{
    /**
     * @var UserProfileDM
     */
    protected $model;

    /**
     * StoreModelEvent constructor.
     * @param UserProfileDM $model
     * @param array $config
     */
    public function __construct(UserProfileDM $model, $config = [])
    {
        $this->model = $model;

        parent::__construct($config);
    }

    /**
     * @return bool|void
     * @throws Exception
     */
    public function run()
    {
        if (!$this->model->user->save()) {
            throw new Exception('Не удается сохранить модель пользователя');
        }
    }
}