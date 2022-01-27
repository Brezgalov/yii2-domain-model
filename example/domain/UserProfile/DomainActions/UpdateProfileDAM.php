<?php

namespace app\domain\UserProfile\DomainActions;

use app\domain\UserProfile\Events\StoreModelEvent;
use app\domain\UserProfile\UserProfileDM;
use app\helpers\PhoneHelper;
use Brezgalov\DomainModel\BaseDomainActionModel;

class UpdateProfileDAM extends BaseDomainActionModel
{
    /**
     * @var UserProfileDM
     */
    protected $model;

    /**
     * @var string
     */
    public $phone;

    /**
     * @return array[]
     */
    public function rules()
    {
        return [
            [['phone'], 'string'],
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function registerInput(array $data = [])
    {
        parent::registerInput(array_merge($this->model->user->toArray(), $data));
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $phoneInput = PhoneHelper::clearPhone($this->phone);

        if ($phoneInput !== $this->model->user->phone) {
            $this->model->user->phone_confirmed_mark = null;
        }

        $this->model->user->phone = $phoneInput;

        $this->model->delayEventByKey(new StoreModelEvent($this->model), UserProfileDM::EVENT_STORE_MODEL);

        return true;
    }
}